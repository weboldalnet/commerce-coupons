<?php

use Illuminate\Support\Facades\Route;
use Weboldalnet\CommerceCoupons\Http\Controllers\Admin\CouponController;

/*
|--------------------------------------------------------------------------
| Kupon modul útvonalai
|--------------------------------------------------------------------------
|
| FIGYELEM: a platformon 'admin_share' a middleware alias, nem 'admin'.
|
| A kuponbeváltás SITE oldali végpontja szándékosan NEM itt van, hanem a
| webshop-ai-default csomagban: a beváltáshoz a kosár kell, ami a webshop
| fogalma. A kupon csomag csak a számítást adja.
|
*/

Route::domain(getAdminDomain())
    ->middleware(['web', 'admin_share', 'auth:admin'])
    ->prefix('webshop/coupons')
    ->name('admin.webshop.coupons.')
    ->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');

        // A FIX szegmensű útvonalak a {coupon} paraméteres elé kell kerüljenek
        Route::post('/toggle-active', [CouponController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/create/{type}', [CouponController::class, 'create'])->name('create');
        Route::post('/', [CouponController::class, 'store'])->name('store');

        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
    });
