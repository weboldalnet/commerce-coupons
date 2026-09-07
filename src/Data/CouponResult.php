<?php

namespace Weboldalnet\CommerceCoupons\Data;

/**
 * Egy kuponbeváltás eredménye.
 *
 * Sikertelen esetben a hibakód gépi döntésre, az üzenet a vásárlónak való.
 * A kettőt szándékosan külön tartjuk: a hívó oldal a kódra ágazhat,
 * a szöveg pedig egy helyen, a kalkulátorban van karbantartva.
 */
class CouponResult
{
    /** Nincs ilyen kuponkód */
    const ERR_NOT_FOUND = 'not_found';
    /** Létezik, de ki van kapcsolva */
    const ERR_INACTIVE = 'inactive';
    /** Az érvényesség még nem kezdődött el */
    const ERR_NOT_STARTED = 'not_started';
    /** Az érvényesség lejárt */
    const ERR_EXPIRED = 'expired';
    /** A kosár nem éri el a minimum értéket */
    const ERR_MIN_CART_TOTAL = 'min_cart_total';
    /** A kosárban nincs olyan tétel, amire a kupon vonatkozna */
    const ERR_NO_MATCHING_ITEMS = 'no_matching_items';
    /** Üres kosár */
    const ERR_EMPTY_CART = 'empty_cart';
    /** A kupon modul ki van kapcsolva */
    const ERR_DISABLED = 'disabled';

    public bool $success;
    public ?string $errorCode;
    public string $message;

    /** A kedvezmény összege (mindig >= 0, kerekítve) */
    public float $discount;

    /** Az az összeg, amire a kupon vonatkozott (a kedvezmény alapja) */
    public float $affectedTotal;

    /** Soronkénti kedvezmény-felosztás: [['product_id'=>int,'discount'=>float], ...] */
    public array $allocation;

    public ?int $couponId;
    public ?string $couponCode;
    public ?string $couponName;

    public function __construct(array $adatok = [])
    {
        $this->success = (bool) ($adatok['success'] ?? false);
        $this->errorCode = $adatok['error_code'] ?? null;
        $this->message = (string) ($adatok['message'] ?? '');
        $this->discount = (float) ($adatok['discount'] ?? 0);
        $this->affectedTotal = (float) ($adatok['affected_total'] ?? 0);
        $this->allocation = $adatok['allocation'] ?? [];
        $this->couponId = $adatok['coupon_id'] ?? null;
        $this->couponCode = $adatok['coupon_code'] ?? null;
        $this->couponName = $adatok['coupon_name'] ?? null;
    }

    public static function failure(string $errorCode, string $message): self
    {
        return new self([
            'success' => false,
            'error_code' => $errorCode,
            'message' => $message,
        ]);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'error_code' => $this->errorCode,
            'message' => $this->message,
            'discount' => $this->discount,
            'affected_total' => $this->affectedTotal,
            'allocation' => $this->allocation,
            'coupon_id' => $this->couponId,
            'coupon_code' => $this->couponCode,
            'coupon_name' => $this->couponName,
        ];
    }
}
