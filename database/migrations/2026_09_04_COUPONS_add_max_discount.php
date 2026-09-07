<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kedvezmény-plafon és kis-nagybetű független egyedi kuponkód.
 *
 * max_discount: százalékos kuponnál a "10%, de legfeljebb 8000 Ft" eset.
 * Fix összegűnél nincs értelme, ott a discount_value maga a plafon.
 *
 * A kód egyedisége eddig kis-nagybetű ÉRZÉKENY volt: felvehető lett volna a
 * "nyar10" és a "NYAR10" is, a beváltás pedig nem tudná eldönteni, melyik az.
 * A funkcionális index az upper(code)-ra épül, a modell pedig nagybetűsítve ment –
 * így a keresés indexet tud használni (egy lower() feltétel nem tudna).
 *
 * FIGYELEM: ez a projekt PostgreSQL 9.4-en fut, ahol a
 * "CREATE INDEX IF NOT EXISTS" MÉG NEM LÉTEZIK (9.5-től van).
 * Ezért a létezést a pg_indexes nézetből kérdezzük meg.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public.commerce_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('public.commerce_coupons', 'max_discount')) {
                $table->decimal('max_discount', 12, 2)->nullable()->after('discount_value');
            }
        });

        // A meglévő kódokat egységesen nagybetűsre hozzuk, különben az új
        // egyedi index ütközne két, csak kis-nagybetűben eltérő kódnál.
        DB::statement('UPDATE public.commerce_coupons SET code = upper(code)');

        /*
           A Schema::create() egyedi indexe mögött UNIQUE MEGSZORÍTÁS áll, ezért
           DROP INDEX-szel nem eltávolítható ("dependent objects still exist").
           A nevébe a sémanév is belekerül, innen a public_ előtag.
        */
        DB::statement('ALTER TABLE public.commerce_coupons
                       DROP CONSTRAINT IF EXISTS public_commerce_coupons_code_unique');

        if (!self::indexLetezik('commerce_coupons_code_upper_unique')) {
            DB::statement('CREATE UNIQUE INDEX commerce_coupons_code_upper_unique
                           ON public.commerce_coupons (upper(code))');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS public.commerce_coupons_code_upper_unique');

        Schema::table('public.commerce_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('public.commerce_coupons', 'max_discount')) {
                $table->dropColumn('max_discount');
            }
        });
    }

    /** Létezik-e az index? (PG 9.4-en nincs CREATE INDEX IF NOT EXISTS) */
    private static function indexLetezik(string $nev): bool
    {
        return (bool) DB::selectOne(
            'select 1 as van from pg_indexes where schemaname = ? and indexname = ?',
            ['public', $nev]
        );
    }
};
