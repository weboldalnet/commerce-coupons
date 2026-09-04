<?php
/**
 * Kupon modul konfiguráció.
 *
 * Csomagváz: a modul tényleges beállításai (kuponfajták, érvényesség,
 * beváltási szabályok) a funkció fejlesztésekor kerülnek ide.
 */
return [
    'enabled' => env('COMMERCE_COUPONS_ENABLED', false),
];
