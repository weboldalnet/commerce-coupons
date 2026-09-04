<?php

namespace Weboldalnet\CommerceCoupons\Support;

class PackageHelper
{
    const PACKAGE_NAME = 'Kupon modul';
    const PACKAGE_PREFIX = 'commerce-coupons';

    const PACKAGE_LIST = [
        'routes' => [
            'name' => 'routes | routes/web.php',
            'source' => __DIR__.'/../../routes/web.php',
            'destination' => '/routes/commerce-coupons.php',
        ],
        'settings' => [
            'name' => 'settings | settings/',
            'source' => __DIR__.'/../../settings',
            'destination' => '/settings/commerce-coupons',
        ],
        'config' => [
            'name' => 'config | config/commerce-coupons.php',
            'source' => __DIR__.'/../../config/commerce-coupons.php',
            'destination' => '/config/commerce-coupons.php',
        ],
    ];

    const PACKAGE_VIEW_EXTENDS = [
        'sidebar' => [
            'view_path' => '/resources/views/admin/package-container/admin-p-sidebar.blade.php',
            'include' => "@include('" . self::PACKAGE_PREFIX . "::admin.sidebar')"
        ],
        'package-settings' => [
            'view_path' => '/resources/views/admin/package-settings/package-settings-container.blade.php',
            'include' => "@include('" . self::PACKAGE_PREFIX . "::admin.package-functions')"
        ],
    ];
}
