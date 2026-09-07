## [1.1.0] - 2026-09-04
### A kupon modul tényleges funkciója

#### Adatmodell
- `commerce_coupons` tábla: egyetlen tábla, `type` + `jsonb targets` – egy új
  kupontípus nem igényel migrációt
- `Coupon` modell: `TYPES`, `DISCOUNT_TYPES` konstansok (nem config, mert cache-elt
  confignál a `mergeConfigFrom` kimarad), `scopeActive`, `scopeValidAt`
- a kuponkód mindig nagybetűsen tárolódik; az egyediség `upper(code)`-ra tett
  funkcionális unique indexszel, kis-nagybetű függetlenül
- `max_discount`: százalékos kupon kedvezményplafonja

#### Beváltási logika
- `CouponService::redeem()` / `apply()` – a kedvezmény számítása és soronkénti
  arányos felosztása; a kerekítési maradék az utolsó sorra kerül, így a
  részösszegek pontosan kiadják a teljes kedvezményt
- `CouponResult` gépi hibakódokkal (`not_found`, `inactive`, `not_started`,
  `expired`, `min_cart_total`, `no_matching_items`, `empty_cart`, `disabled`)
  és a vásárlónak szánt magyar üzenettel

#### Admin felület
- kupon lista típuskártyákkal, aktív kapcsolóval és törléssel
- típusonkénti szerkesztő: termékkereső / kategóriaválasztó / célzás nélküli kosár
- a típus utólag nem módosítható (a `targets` szerkezete típusfüggő)
- fix összegű kuponnál a szerver is kinullázza a `max_discount`-ot

#### Dokumentáció
- README átírva a csomagvázról a tényleges funkcióra, a tervezési döntésekkel
  és az „új kupontípus felvétele" recepttel
- a config fájl és az oldalsáv-részlet megjegyzései tisztázzák, hogy a BE/KI
  kapcsoló webshop projektben a `coupons_enabled` beállítás, és hogy az oldalsáv
  részletet ott NEM kell publikálni (dupla menüpontot adna)

## [1.0.0] - 2026-09-04
### Csomagváz a kupon modulhoz
- package-template elnevezések lecserélve (`Weboldalnet\CommerceCoupons` névtér)
- `CommerceCouponsServiceProvider`, `commerce-coupons:install`, `commerce-coupons:extend`
- `config/commerce-coupons.php`, üres `routes/web.php` váz
- composer csomagnév: `weboldalnet/commerce-coupons`, függőség: `weboldalnet/commerce-core:^1.0`
