<?php
/**
 * Kupon modul konfiguráció.
 *
 * FIGYELEM: a modul BE/KI kapcsolója NEM itt van.
 *
 * Webshop projektben a kapcsoló a Webshop beállítások "Kuponok engedélyezve"
 * mezője (`coupons_enabled` beállítás), mert azt a bolt tulajdonosa is át
 * tudja állítani – ehhez nem kell .env módosítás és cache ürítés.
 *
 * Ez az érték csak akkor jön szóba, ha a modult webshop csomag NÉLKÜLI
 * projektbe teszik, és a befogadó maga dönti el, honnan olvassa a kapcsolót.
 *
 * A típusfüggő igazságok (kupontípusok, kedvezménytípusok) szándékosan a
 * Coupon modell konstansaiban vannak, nem itt: a befogadó projektben létezhet
 * bootstrap/cache/config.php, és cache-elt confignál a csomag mergeConfigFrom-ja
 * kimarad – egy config('commerce-coupons.…') hívás ilyenkor null-t adna.
 */
return [
    'enabled' => env('COMMERCE_COUPONS_ENABLED', false),
];
