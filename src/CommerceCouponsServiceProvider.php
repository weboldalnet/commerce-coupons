<?php

namespace Weboldalnet\CommerceCoupons;

use Illuminate\Support\ServiceProvider;
use Weboldalnet\CommerceCoupons\Support\PackageHelper;
use Weboldalnet\CommerceCoupons\Console\ExtendViewsCommerceCouponsCommand;
use Weboldalnet\CommerceCoupons\Console\InstallCommerceCouponsCommand;

class CommerceCouponsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // route-ok és admin nézetek
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../settings/views', PackageHelper::PACKAGE_PREFIX);

        // Az admin felület nézetei. Gyökér szintű hozzáadás, hogy a
        // view('admin.webshop.coupons...') hívás a testvércsomagokkal azonos
        // módon működjön; a befogadó projekt resources/views-je felülírja.
        $this->app['view']->addLocation(__DIR__.'/../resources/views');

        // A modul saját tábláit a csomag hozza; publish nem kell hozzájuk.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $publishList = [];
        foreach (PackageHelper::PACKAGE_LIST as $name => $publish) {
            $this->publishes([
                $publish['source'] => base_path($publish['destination']),
            ], PackageHelper::PACKAGE_PREFIX . '-' . $name);

            $publishList[$publish['source']] = base_path($publish['destination']);
        }

        $this->publishes($publishList, PackageHelper::PACKAGE_PREFIX . '-all');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/commerce-coupons.php', 'commerce-coupons');

        $this->commands([
            InstallCommerceCouponsCommand::class,
            ExtendViewsCommerceCouponsCommand::class,
        ]);
    }
}
