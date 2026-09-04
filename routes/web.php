<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kupon modul útvonalai
|--------------------------------------------------------------------------
|
| Csomagváz: a kuponbeváltás és a kuponkezelő admin útvonalai a modul
| fejlesztésekor kerülnek ide, a testvércsomagok mintájára.
|
| FIGYELEM: a platformon 'admin_share' a middleware alias, nem 'admin'.
|
| Route::domain(getSiteDomain())
|     ->middleware(['web', 'site_share'])
|     ->group(function () {
|         ...
|     });
|
| Route::domain(getAdminDomain())
|     ->middleware(['web', 'admin_share', 'auth:admin'])
|     ->prefix('webshop/coupons')
|     ->name('admin.webshop.coupons.')
|     ->group(function () {
|         ...
|     });
|
*/
