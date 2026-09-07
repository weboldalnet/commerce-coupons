<?php

namespace Weboldalnet\CommerceCoupons\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Egy kupon.
 *
 * Minden típusfüggő igazság (címke, ikon, célzó mező) ITT, konstansban van,
 * nem configban: a befogadó projektben létezhet bootstrap/cache/config.php,
 * és cache-elt confignál a csomag mergeConfigFrom-ja kimarad – egy
 * config('commerce-coupons.…') hívás ilyenkor null-t adna vissza.
 */
class Coupon extends Model
{
    protected $table = 'public.commerce_coupons';

    protected $fillable = [
        'name', 'code', 'type',
        'discount_type', 'discount_value', 'max_discount',
        'starts_at', 'ends_at',
        'min_cart_total', 'is_active', 'targets',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'max_discount' => 'float',
        'min_cart_total' => 'float',
        'is_active' => 'boolean',
        'targets' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /** Kuponra kiválasztott termékek */
    const TYPE_PRODUCT = 'product';
    /** Egy kiválasztott kategória termékei */
    const TYPE_CATEGORY = 'category';
    /** A teljes kosárérték */
    const TYPE_CART = 'cart';

    /**
     * Kupontípusok. A 4. típus ára: egy sor itt + egy ág a kalkulátorban
     * + egy admin nézet-részlet.
     */
    const TYPES = [
        self::TYPE_PRODUCT => [
            'label' => 'Termék kupon',
            'icon' => 'fa-box',
            'description' => 'Egyesével kiválasztott termékekre vonatkozik.',
        ],
        self::TYPE_CATEGORY => [
            'label' => 'Kategória kupon',
            'icon' => 'fa-folder-open',
            'description' => 'Egy kiválasztott kategória termékeire vonatkozik.',
        ],
        self::TYPE_CART => [
            'label' => 'Kosár kupon',
            'icon' => 'fa-shopping-basket',
            'description' => 'A teljes kosárértékre vonatkozik.',
        ],
    ];

    const DISCOUNT_PERCENT = 'percent';
    const DISCOUNT_FIXED = 'fixed';

    const DISCOUNT_TYPES = [
        self::DISCOUNT_PERCENT => 'Százalékos kedvezmény',
        self::DISCOUNT_FIXED => 'Fix összegű kedvezmény',
    ];

    /**
     * A kuponkódot mindig nagybetűsen tároljuk.
     *
     * Így az egyediség kis-nagybetű független (funkcionális index az
     * upper(code)-ra), és a beváltás egy egyszerű egyenlőséggel keres,
     * ami indexet tud használni.
     */
    public function setCodeAttribute($ertek): void
    {
        $this->attributes['code'] = mb_strtoupper(trim((string) $ertek));
    }

    /** Kupon keresése a vásárló által beírt kód alapján */
    public static function findByCode(string $kod): ?self
    {
        return static::where('code', mb_strtoupper(trim($kod)))->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Az adott időpontban érvényes kuponok.
     *
     * A határokat ZÁRTNAK vesszük: a kezdő és a záró időpont még beleszámít.
     */
    public function scopeValidAt($query, $idopont = null)
    {
        $idopont = $idopont ?: now();

        return $query->where('starts_at', '<=', $idopont)
            ->where('ends_at', '>=', $idopont);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? $this->type;
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPES[$this->type]['icon'] ?? 'fa-ticket';
    }

    public function isPercent(): bool
    {
        return $this->discount_type === self::DISCOUNT_PERCENT;
    }

    /** A célzáshoz mentett termékazonosítók (termék kupon) */
    public function productIds(): array
    {
        $idk = $this->targets['product_ids'] ?? [];

        return is_array($idk) ? array_map('intval', $idk) : [];
    }

    /** A célzáshoz mentett kategória (kategória kupon) */
    public function categoryId(): ?int
    {
        $id = $this->targets['category_id'] ?? null;

        return $id ? (int) $id : null;
    }

    /**
     * Érvényes-e most (aktív ÉS az időintervallumon belül)?
     * Az aktiválás további feltételeit (minimum kosárérték, érintett tétel)
     * a kalkulátor vizsgálja, mert azokhoz a kosár is kell.
     */
    public function isValidNow(): bool
    {
        return $this->is_active
            && $this->starts_at
            && $this->ends_at
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
    }
}
