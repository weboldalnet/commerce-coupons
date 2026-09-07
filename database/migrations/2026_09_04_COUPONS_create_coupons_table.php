<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kuponok.
 *
 * Egy tábla, típus szerinti megkülönböztetéssel: az alapadatok (név, kód,
 * kedvezmény, érvényesség, minimum kosárérték) MINDEN típusnál azonosak,
 * és csak a célzás tér el. Ezt a különbséget a targets jsonb hordozza,
 * így egy új kupontípus nem igényel új táblát vagy migrációt.
 *
 * A kód egyedi: erre keres a beváltás, és két azonos kódú kupon esetén
 * nem lenne eldönthető, melyik érvényes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public.commerce_coupons')) {
            return;
        }

        Schema::create('public.commerce_coupons', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code', 64)->unique();

            // product | category | cart
            $table->string('type', 30)->index();

            // percent | fixed
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');

            // Opcionális küszöb: ez alatt a kosárérték alatt nem aktiválható
            $table->decimal('min_cart_total', 12, 2)->nullable();

            $table->boolean('is_active')->default(true)->index();

            // Típusfüggő célzás: {"product_ids":[1,2]} vagy {"category_id":5}
            $table->jsonb('targets')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public.commerce_coupons');
    }
};
