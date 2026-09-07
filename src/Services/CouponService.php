<?php

namespace Weboldalnet\CommerceCoupons\Services;

use Weboldalnet\CommerceCoupons\Data\CouponResult;
use Weboldalnet\CommerceCoupons\Models\Coupon;

/**
 * A kuponbeváltás EGYETLEN igazságforrása.
 *
 * A pénztár és a rendelés létrehozása is EZT hívja, ugyanazzal a bemenettel.
 * Enélkül a kiírt és a ténylegesen felszámított kedvezmény elcsúszhatna –
 * pontosan ez a csapda buktatta korábban az utánvét-felárat, ahol két helyen
 * futott a számítás.
 *
 * A csomag SZÁNDÉKOSAN nem ismeri a webshop modelljeit: a kosarat normalizált
 * tömbként kapja, így a modul bármelyik commerce-core alapú boltba beköthető.
 *
 * Egy kosársor alakja:
 *   ['product_id' => int, 'category_id' => ?int, 'unit_price' => float, 'quantity' => int]
 */
class CouponService
{
    /**
     * Kupon beváltása kód alapján.
     *
     * @param string $kod        a vásárló által beírt kuponkód
     * @param array  $kosarSorok normalizált kosársorok
     */
    public static function redeem(string $kod, array $kosarSorok): CouponResult
    {
        $kod = trim($kod);

        if ($kod === '') {
            return CouponResult::failure(
                CouponResult::ERR_NOT_FOUND,
                'Add meg a kuponkódot.'
            );
        }

        if (empty($kosarSorok)) {
            return CouponResult::failure(
                CouponResult::ERR_EMPTY_CART,
                'A kosár üres, így nincs mire kupont alkalmazni.'
            );
        }

        // A kód mindig nagybetűsen van tárolva, ezért a keresés egyszerű
        // egyenlőség – indexet tud használni, szemben egy lower() feltétellel.
        $kupon = Coupon::findByCode($kod);

        if (!$kupon) {
            return CouponResult::failure(
                CouponResult::ERR_NOT_FOUND,
                'Nincs ilyen kuponkód.'
            );
        }

        return self::apply($kupon, $kosarSorok);
    }

    /**
     * Egy KONKRÉT kupon alkalmazása a kosárra.
     *
     * A rendelés létrehozásakor ezt hívjuk (a kupon már azonosított), a
     * beváltásnál a redeem() ezen keresztül fut le. Így a validáció és a
     * számítás mindkét úton azonos.
     */
    public static function apply(Coupon $kupon, array $kosarSorok): CouponResult
    {
        if (!$kupon->is_active) {
            return CouponResult::failure(
                CouponResult::ERR_INACTIVE,
                'Ez a kupon jelenleg nem használható.'
            );
        }

        $most = now();

        if ($kupon->starts_at && $kupon->starts_at->gt($most)) {
            return CouponResult::failure(
                CouponResult::ERR_NOT_STARTED,
                'Ez a kupon még nem érvényes. Érvényesség kezdete: '
                . $kupon->starts_at->format('Y.m.d H:i') . '.'
            );
        }

        if ($kupon->ends_at && $kupon->ends_at->lt($most)) {
            return CouponResult::failure(
                CouponResult::ERR_EXPIRED,
                'Ez a kupon lejárt.'
            );
        }

        $kosarOsszeg = self::sorokOsszege($kosarSorok);

        // A minimum küszöb a TELJES kosárértékre vonatkozik, nem csak arra a
        // részére, amire a kupon érvényes – így értelmezi a vásárló is.
        if ($kupon->min_cart_total && $kosarOsszeg < $kupon->min_cart_total) {
            return CouponResult::failure(
                CouponResult::ERR_MIN_CART_TOTAL,
                'Ehhez a kuponhoz legalább ' . self::huf($kupon->min_cart_total)
                . ' kosárérték szükséges.'
            );
        }

        $erintettSorok = self::erintettSorok($kupon, $kosarSorok);
        $erintettOsszeg = self::sorokOsszege($erintettSorok);

        if ($erintettOsszeg <= 0) {
            return CouponResult::failure(
                CouponResult::ERR_NO_MATCHING_ITEMS,
                'A kosaradban nincs olyan termék, amire ez a kupon vonatkozna.'
            );
        }

        $kedvezmeny = self::kedvezmeny($kupon, $erintettOsszeg);

        return new CouponResult([
            'success' => true,
            'message' => 'A kupon sikeresen aktiválva.',
            'discount' => $kedvezmeny,
            'affected_total' => $erintettOsszeg,
            'allocation' => self::felosztas($erintettSorok, $erintettOsszeg, $kedvezmeny),
            'coupon_id' => $kupon->id,
            'coupon_code' => $kupon->code,
            'coupon_name' => $kupon->name,
        ]);
    }

    /**
     * A kupon hatókörébe eső kosársorok.
     *
     * Ismeretlen típusnál üres tömb: egy adatbázisban maradt, kivezetett
     * típus így nem ad néma kedvezményt, hanem "nincs érintett tétel" hibát.
     */
    protected static function erintettSorok(Coupon $kupon, array $kosarSorok): array
    {
        switch ($kupon->type) {
            case Coupon::TYPE_CART:
                return $kosarSorok;

            case Coupon::TYPE_PRODUCT:
                $idk = $kupon->productIds();

                return array_values(array_filter($kosarSorok, function ($sor) use ($idk) {
                    return in_array((int) ($sor['product_id'] ?? 0), $idk, true);
                }));

            case Coupon::TYPE_CATEGORY:
                $kategoriaId = $kupon->categoryId();

                if (!$kategoriaId) {
                    return [];
                }

                return array_values(array_filter($kosarSorok, function ($sor) use ($kategoriaId) {
                    return (int) ($sor['category_id'] ?? 0) === $kategoriaId;
                }));

            default:
                return [];
        }
    }

    /**
     * A kedvezmény összege.
     *
     * Két szabály, amit sosem sértünk meg:
     *  - a kedvezmény nem lehet több, mint amire vonatkozik (fix összegnél
     *    ez valós eset: 5000 Ft kupon egy 3000 Ft-os termékre);
     *  - egész forintra kerekítünk, mert a bolt egész forintot jelenít meg,
     *    és a tizedes maradék a kiírt és a felszámított összeg között
     *    látszólagos eltérést okozna.
     */
    protected static function kedvezmeny(Coupon $kupon, float $erintettOsszeg): float
    {
        if ($kupon->isPercent()) {
            // A százalékot 0 és 100 közé szorítjuk: hibás adat ne adhasson
            // negatív vagy a teljes összegnél nagyobb kedvezményt.
            $szazalek = max(0, min(100, $kupon->discount_value));
            $kedvezmeny = $erintettOsszeg * $szazalek / 100;

            // Opcionális plafon: "10%, de legfeljebb 8000 Ft".
            // Fix összegűnél nincs értelme, ott a discount_value maga a plafon.
            if ($kupon->max_discount && $kupon->max_discount > 0) {
                $kedvezmeny = min($kedvezmeny, $kupon->max_discount);
            }
        } else {
            $kedvezmeny = max(0, $kupon->discount_value);
        }

        return round(min($kedvezmeny, $erintettOsszeg));
    }

    /**
     * A kedvezmény arányos szétosztása az érintett tételsorokra.
     *
     * Miért kell: a fizetési és a számlázási integrációk tételes listát kapnak,
     * és annak az összegének BITRE egyeznie kell a rendelés végösszegével.
     * Ha csak egy összevont kedvezményt adnánk, a tételek összege és a fizetendő
     * eltérne – a szolgáltató pedig ezt visszautasítja.
     *
     * A kerekítési maradékot az UTOLSÓ sorra tesszük, így az összeg pontosan
     * kiadja a kedvezményt (soronkénti kerekítésnél 1-2 Ft elcsúszna).
     *
     * @return array<int, array{product_id:int, discount:float}>
     */
    protected static function felosztas(array $erintettSorok, float $erintettOsszeg, float $kedvezmeny): array
    {
        $felosztas = [];

        if ($erintettOsszeg <= 0 || $kedvezmeny <= 0) {
            return $felosztas;
        }

        $eddigKiosztott = 0.0;
        $utolsoIndex = count($erintettSorok) - 1;

        foreach (array_values($erintettSorok) as $index => $sor) {
            $sorOsszeg = ((float) ($sor['unit_price'] ?? 0)) * ((int) ($sor['quantity'] ?? 0));

            if ($index === $utolsoIndex) {
                $resz = round($kedvezmeny - $eddigKiosztott, 2);
            } else {
                $resz = round($kedvezmeny * $sorOsszeg / $erintettOsszeg);
                $eddigKiosztott += $resz;
            }

            $felosztas[] = [
                'product_id' => (int) ($sor['product_id'] ?? 0),
                'discount' => $resz,
            ];
        }

        return $felosztas;
    }

    protected static function sorokOsszege(array $sorok): float
    {
        $osszeg = 0.0;

        foreach ($sorok as $sor) {
            $osszeg += ((float) ($sor['unit_price'] ?? 0)) * ((int) ($sor['quantity'] ?? 0));
        }

        return $osszeg;
    }

    protected static function huf(float $osszeg): string
    {
        return number_format($osszeg, 0, ',', ' ') . ' Ft';
    }

    /**
     * Van-e egyáltalán MOST érvényes kupon?
     *
     * A pénztár ezzel dönti el, kirajzolja-e a beváltó dobozt – így nem
     * mutatunk üresbe futó mezőt, ha épp egyetlen kupon sem él.
     */
    public static function hasValidCoupons(): bool
    {
        return Coupon::active()->validAt()->exists();
    }
}
