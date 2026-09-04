# Kupon modul a commerce-core-hoz

Ez a csomag a kupon- és kedvezménykezelést adja a `weboldalnet/commerce-core` alapú rendszerekhez.

> **Állapot:** csomagváz. A struktúra és az elnevezések készen állnak (composer autoload,
> service provider, publish/extend parancsok, config), a tényleges funkció (modellek,
> migrációk, beváltási logika, pénztár-integráció, admin felület, útvonalak) még nincs megírva.

A provider-csomagoktól (SimplePay, FoxPost, GLS, Barion, Számlázz.hu) eltérően ez nem
fizetési/szállítási/számlázási szolgáltató, ezért nem jelentkezik be a `commerce-core`
provider registry-jébe – a saját admin felületét maga hozza magával.

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
```

A service provider Laravel package auto-discovery-vel regisztrálódik
(`Weboldalnet\CommerceCoupons\CommerceCouponsServiceProvider`).

## Konfiguráció

Publikálás a projektbe:

```bash
php artisan commerce-coupons:install --tag=commerce-coupons-all
php artisan commerce-coupons:extend --view=all
```

Publikálható tagek:

| tag | tartalom |
| --- | --- |
| `commerce-coupons-routes` | `routes/web.php` → `routes/commerce-coupons.php` |
| `commerce-coupons-settings` | `settings/` → `settings/commerce-coupons` |
| `commerce-coupons-config` | `config/commerce-coupons.php` |
| `commerce-coupons-all` | mindegyik |

`.env` beállítások:

```env
COMMERCE_COUPONS_ENABLED=false
```

## Névterek és fájlszerkezet

```
src/CommerceCouponsServiceProvider.php             – service provider (publish, route, view betöltés)
src/Console/InstallCommerceCouponsCommand.php      – commerce-coupons:install
src/Console/ExtendViewsCommerceCouponsCommand.php  – commerce-coupons:extend
src/Support/PackageHelper.php                      – publish lista és view kiegészítések
config/commerce-coupons.php                        – konfiguráció
routes/web.php                                     – útvonalak (egyelőre üres váz)
settings/views/admin/                              – admin sidebar és package-functions blade
```
