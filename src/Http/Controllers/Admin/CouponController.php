<?php

namespace Weboldalnet\CommerceCoupons\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminExtendedController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Weboldalnet\CommerceCoupons\Models\Coupon;

/**
 * Kuponkezelő admin felület.
 *
 * A termék- és kategórialistát a BEFOGADÓ projekt modelljeiből kérjük le, de
 * SOHA nem hivatkozunk rájuk közvetlenül: az osztálynevek stringként, class_exists()
 * mögött szerepelnek. Így a modul olyan boltba is telepíthető, ahol más a
 * termékmodell – ott a választók egyszerűen üresek maradnak.
 */
class CouponController extends AdminExtendedController
{
    /** A webshop csomag modelljei – csak stringként hivatkozva */
    const PRODUCT_MODEL = '\Weboldalnet\WebshopAiDefault\Models\WebshopProduct';
    const CATEGORY_MODEL = '\Weboldalnet\WebshopAiDefault\Models\WebshopCategory';

    public function index()
    {
        return view('admin.webshop.coupons.index', [
            'coupons' => Coupon::orderByDesc('id')->get(),
            'types' => Coupon::TYPES,
        ]);
    }

    public function create(string $type)
    {
        if (!array_key_exists($type, Coupon::TYPES)) {
            return redirect()->route('admin.webshop.coupons.index')
                ->with('error', 'Ismeretlen kupontípus.');
        }

        $coupon = new Coupon(['type' => $type, 'discount_type' => Coupon::DISCOUNT_PERCENT]);

        return view('admin.webshop.coupons.form', $this->formData($coupon));
    }

    public function edit($coupon)
    {
        return view('admin.webshop.coupons.form', $this->formData(Coupon::findOrFail($coupon)));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        if (!array_key_exists($type, Coupon::TYPES)) {
            return redirect()->route('admin.webshop.coupons.index')
                ->with('error', 'Ismeretlen kupontípus.');
        }

        $coupon = Coupon::create($this->validated($request, $type));

        return redirect()->route('admin.webshop.coupons.edit', $coupon->id)
            ->with('success', 'Kupon létrehozva.');
    }

    public function update(Request $request, $coupon)
    {
        $coupon = Coupon::findOrFail($coupon);
        $coupon->update($this->validated($request, $coupon->type, $coupon->id));

        return redirect()->route('admin.webshop.coupons.edit', $coupon->id)
            ->with('success', 'Kupon elmentve.');
    }

    public function destroy($coupon)
    {
        Coupon::findOrFail($coupon)->delete();

        return redirect()->route('admin.webshop.coupons.index')->with('success', 'Kupon törölve.');
    }

    /**
     * Aktív kapcsoló (AJAX).
     * A segéd JS STRINGKÉNT küldi az értéket, ezért nem boolean() a vizsgálat.
     */
    public function toggleActive(Request $request)
    {
        $coupon = Coupon::findOrFail($request->input('id'));
        $ertek = $request->input('is_active');

        $coupon->update(['is_active' => $ertek === 'true' || $ertek === true || $ertek === '1']);

        return response()->json(['success' => true, 'message' => 'Állapot mentve.']);
    }

    /**
     * Az űrlap adatai. A termék- és kategórialista csak akkor töltődik be,
     * ha az adott típusnál egyáltalán szükség van rá.
     */
    protected function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'isEdit' => (bool) $coupon->exists,
            'types' => Coupon::TYPES,
            'discountTypes' => Coupon::DISCOUNT_TYPES,
            'categoryOptions' => $coupon->type === Coupon::TYPE_CATEGORY ? self::categoryOptions() : [],
            'selectedProducts' => $coupon->type === Coupon::TYPE_PRODUCT ? self::products($coupon->productIds()) : [],
        ];
    }

    /**
     * Az űrlap ellenőrzése és a mentendő mezők összeállítása.
     *
     * A kód egyediségét kis-nagybetű függetlenül nézzük, mert a modell
     * nagybetűsítve tárol – enélkül a "nyar10" és a "NYAR10" felvehető lenne,
     * és a beváltásnál nem lenne eldönthető, melyik az érvényes.
     */
    protected function validated(Request $request, string $type, ?int $ignoreId = null): array
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:64',
            'discount_type' => 'required|in:' . implode(',', array_keys(Coupon::DISCOUNT_TYPES)),
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'min_cart_total' => 'nullable|numeric|min:0',
        ], [], [
            'name' => 'kupon neve',
            'code' => 'kupon kód',
            'ends_at' => 'érvényesség vége',
        ]);

        $kod = mb_strtoupper(trim($request->input('code')));

        $utkozik = Coupon::where('code', $kod)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($utkozik) {
            // ValidationException: a Laravel ebből magától csinál vissza-irányítást
            // a beírt adatokkal és a hibaüzenettel – egy return itt nem működne,
            // mert ez a metódus tömböt ad vissza.
            throw ValidationException::withMessages([
                'code' => 'Ez a kuponkód már foglalt (a kis- és nagybetűk nem különböznek).',
            ]);
        }

        return [
            'name' => $request->input('name'),
            'code' => $kod,
            'type' => $type,
            'discount_type' => $request->input('discount_type'),
            'discount_value' => (float) $request->input('discount_value'),
            // A plafon csak százalékos kuponnál értelmes
            'max_discount' => $request->input('discount_type') === Coupon::DISCOUNT_PERCENT
                ? ($request->input('max_discount') !== null && $request->input('max_discount') !== ''
                    ? (float) $request->input('max_discount') : null)
                : null,
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
            'min_cart_total' => $request->input('min_cart_total') !== null && $request->input('min_cart_total') !== ''
                ? (float) $request->input('min_cart_total') : null,
            'is_active' => $request->has('is_active'),
            'targets' => self::targets($request, $type),
        ];
    }

    /** A típusfüggő célzás. Új típus ára: egy ág itt + egy nézet-részlet. */
    protected static function targets(Request $request, string $type): array
    {
        switch ($type) {
            case Coupon::TYPE_PRODUCT:
                $idk = $request->input('product_ids', []);
                $tiszta = [];

                foreach (is_array($idk) ? $idk : [] as $id) {
                    $id = (int) $id;
                    if ($id > 0 && !in_array($id, $tiszta, true)) {
                        $tiszta[] = $id;
                    }
                }

                return ['product_ids' => $tiszta];

            case Coupon::TYPE_CATEGORY:
                $id = (int) $request->input('category_id');

                return ['category_id' => $id > 0 ? $id : null];

            default:
                // Kosár kupon: nincs célzás, a teljes kosárra vonatkozik
                return [];
        }
    }

    /** Kategóriák a legördülőhöz, szülő -> gyerek behúzással */
    protected static function categoryOptions(): array
    {
        $osztaly = self::CATEGORY_MODEL;

        if (!class_exists($osztaly)) {
            return [];
        }

        $kategoriak = $osztaly::orderBy('sort_order')->get();
        $gyerekek = $kategoriak->groupBy('parent_id');
        $lista = [];

        $bejar = function ($szuloId, $szint) use (&$bejar, $gyerekek, &$lista) {
            foreach ($gyerekek->get($szuloId, collect()) as $kategoria) {
                $lista[$kategoria->id] = str_repeat('— ', $szint) . $kategoria->name_singular;
                $bejar($kategoria->id, $szint + 1);
            }
        };

        $bejar(null, 0);

        return $lista;
    }

    /**
     * A kiválasztott termékek a MENTETT SORRENDBEN.
     * A hiányzókat is visszaadjuk helyőrzőként, hogy egy időközben törölt
     * termék ne tűnjön el némán a szerkesztőből.
     */
    protected static function products(array $idk): array
    {
        $osztaly = self::PRODUCT_MODEL;

        if (empty($idk) || !class_exists($osztaly)) {
            return [];
        }

        $termekek = $osztaly::whereIn('id', $idk)->get()->keyBy('id');
        $eredmeny = [];

        foreach ($idk as $id) {
            $eredmeny[] = isset($termekek[$id])
                ? ['id' => $id, 'name' => $termekek[$id]->name, 'sku' => $termekek[$id]->sku, 'missing' => false]
                : ['id' => $id, 'name' => 'Törölt termék', 'sku' => null, 'missing' => true];
        }

        return $eredmeny;
    }
}
