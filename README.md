# Kupon modul a commerce-core-hoz

Ez a csomag a kupon- és kedvezménykezelést adja a `weboldalnet/commerce-core` alapú
rendszerekhez: kuponok felvétele az adminban, a kedvezmény kiszámítása, és — webshop
projektben — a pénztári beváltás.

A provider-csomagoktól (SimplePay, FoxPost, GLS, Barion, Számlázz.hu) eltérően ez nem
fizetési/szállítási/számlázási szolgáltató, ezért nem jelentkezik be a `commerce-core`
provider registry-jébe – a saját admin felületét maga hozza magával.

## Mit tud

Három kupontípus:

| típus | mire vonatkozik |
| --- | --- |
| `product` | egyenként kiválasztott termékekre |
| `category` | egy kiválasztott kategória termékeire |
| `cart` | a teljes kosárértékre |

Mindegyik lehet **százalékos** vagy **fix összegű**, és mindegyikhez megadható:

- érvényességi időszak (kezdet / vég)
- minimum kosárérték
- kedvezmény felső határa (csak százalékosnál)
- aktív / inaktív kapcsoló

## Telepítés

A projekt `composer.json`-jában:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/weboldalnet/commerce-coupons"
    }
]
```

```bash
composer require weboldalnet/commerce-coupons:^1.0
php artisan migrate
```

A service provider Laravel package auto-discovery-vel regisztrálódik
(`Weboldalnet\CommerceCoupons\CommerceCouponsServiceProvider`), a migrációk, az
útvonalak és a nézetek külön publish nélkül betöltődnek.

### Webshop projektben

A modul BE/KI kapcsolója a **Webshop beállítások → „Kuponok engedélyezve"** mező
(`coupons_enabled` beállítás). Ez adja:

- az admin oldalsáv „Kuponok" menüpontját (a `webshop-ai-default` sidebarjából jön)
- a pénztári kuponbeváltó dobozt

A `webshop-ai-default` csomag hozza a kapcsolót, a pénztári integrációt és a
`webshop_orders` tábla kupon-oszlopait (`coupon_id`, `coupon_code`,
`discount_amount`, `coupon_data`).

**Ne publikáld** a `commerce-coupons-settings` taget webshop projektben: az abban lévő
oldalsáv-részlet ugyanazt a menüpontot adná még egyszer.

### Webshop csomag nélkül

Ilyenkor a befogadó projekt dolga:

1. az oldalsáv-részlet publikálása (`commerce-coupons-settings`) és a benne lévő
   megjegyzésjelek törlése
2. a beváltás bekötése: a `CouponService` kosársorokat vár, a kosár fogalma nem a
   kupon modulé

## Publikálható tagek

```bash
php artisan commerce-coupons:install --tag=commerce-coupons-all
php artisan commerce-coupons:extend --view=all
```

| tag | tartalom | webshop projektben kell? |
| --- | --- | --- |
| `commerce-coupons-routes` | `routes/web.php` → `routes/commerce-coupons.php` | nem – a csomag magától betölti |
| `commerce-coupons-settings` | `settings/` → `settings/commerce-coupons` | **nem** – dupla menüpontot adna |
| `commerce-coupons-config` | `config/commerce-coupons.php` | nem – a kapcsoló a webshop beállítás |
| `commerce-coupons-all` | mindegyik | nem |

Friss telepítéskor tehát **nincs kötelező publish** – elég a `composer require` és a
`php artisan migrate`.

## Használat kódból

```php
use Weboldalnet\CommerceCoupons\Services\CouponService;

// Egy kosársor formája:
//   ['product_id' => 1, 'category_id' => 4, 'unit_price' => 2490, 'quantity' => 2]
// A sor összegét a szolgáltatás számolja (unit_price * quantity), hogy a hívó
// oldal ne tudjon kész összeget becsempészni.
$eredmeny = CouponService::redeem('NYAR20', $kosarSorok);   // CouponResult objektum

if ($eredmeny->success) {
    $kedvezmeny = $eredmeny->discount;      // forint
    $felosztas  = $eredmeny->allocation;    // soronkénti bontás, összegük pontosan = discount
} else {
    $uzenet  = $eredmeny->message;          // magyar hibaüzenet a vásárlónak
    $hibaKod = $eredmeny->errorCode;        // gépi döntésre (CouponResult::ERR_*)
}

// JSON válaszhoz / tároláshoz:
$tomb = $eredmeny->toArray();               // 'success', 'error_code', 'discount', 'allocation', …
```

Hibakódok: `not_found`, `inactive`, `not_started`, `expired`, `min_cart_total`,
`no_matching_items`, `empty_cart`, `disabled`.

## Fontos tervezési döntések

- **A kuponkód mindig nagybetűsen tárolódik** (`setCodeAttribute`), az egyediséget egy
  `upper(code)`-ra tett funkcionális unique index adja. Így a beváltás egyszerű
  egyenlőséggel keres, ami indexet tud használni, és a „nyar10" / „NYAR10" nem lehet
  két külön kupon.
- **A session csak a kuponKÓDOT tárolja, az összeget soha.** Minden megjelenítésnél és a
  rendelés leadásakor újraszámolunk – így egy közben lejárt vagy módosított kupon nem
  tud elavult összeggel érvényesülni.
- **A kedvezmény felosztása** (`allocation`) arányos, a kerekítési maradék az utolsó
  sorra kerül, így a részösszegek pontosan kiadják a teljes kedvezményt.
- **A típusfüggő igazságok a `Coupon` modell konstansaiban vannak, nem configban.** A
  befogadó projektben létezhet `bootstrap/cache/config.php`; cache-elt confignál a
  csomag `mergeConfigFrom`-ja kimarad, és egy `config('commerce-coupons.…')` null-t adna.
- **A kupon típusa utólag nem szerkeszthető.** A `targets` szerkezete típusfüggő, egy
  váltás értelmezhetetlen célzást hagyna a rekordon.

## Új kupontípus felvétele

Három hely:

1. egy sor a `Coupon::TYPES` konstansban (címke, ikon, leírás)
2. egy ág a `CouponService::erintettSorok()`-ban
3. egy új `resources/views/admin/webshop/coupons/targets/<típus>.blade.php`

A közös admin űrlap, a lista és a pénztári doboz nem módosul.

## Névterek és fájlszerkezet

```
src/CommerceCouponsServiceProvider.php             – service provider (publish, route, view, migráció)
src/Console/InstallCommerceCouponsCommand.php      – commerce-coupons:install
src/Console/ExtendViewsCommerceCouponsCommand.php  – commerce-coupons:extend
src/Support/PackageHelper.php                      – publish lista és view kiegészítések
src/Models/Coupon.php                              – a kupon modell (típus- és kedvezménykonstansokkal)
src/Data/CouponResult.php                          – a beváltás eredménye és a hibakódok
src/Services/CouponService.php                     – a beváltási és számítási logika
src/Http/Controllers/Admin/CouponController.php    – admin CRUD
database/migrations/                               – commerce_coupons tábla
resources/views/admin/webshop/coupons/             – admin lista + űrlap + típusonkénti célzás
config/commerce-coupons.php                        – konfiguráció (a kapcsoló NEM itt van)
routes/web.php                                     – admin útvonalak
settings/views/admin/                              – oldalsáv és package-functions blade
```

## Függőségek

- PHP ^8.2
- `weboldalnet/commerce-core:^1.0`
- adatbázis: PostgreSQL (a migráció `jsonb` oszlopot használ)
