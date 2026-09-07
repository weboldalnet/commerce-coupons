{{-- Kupon modul admin menüpontja.

     WEBSHOP PROJEKTBEN EZT NE PUBLIKÁLD.

     A webshop-ai-default csomag saját oldalsávja már tartalmazza a "Kuponok"
     menüpontot a Webshop csoportban (a `coupons_enabled` beállítás és a
     Route::has('admin.webshop.coupons.index') együttes feltételével). Ha ezt
     is bekapcsolod, a menüpont kétszer jelenik meg.

     Ez a részlet azoknak a projekteknek szól, ahol a modul webshop csomag
     NÉLKÜL fut – ott ez adja az egyetlen belépési pontot. Ilyenkor töröld a
     köréje írt megjegyzésjeleket.

     Megjegyzés: a provider-csomagoktól (SimplePay, FoxPost, GLS) eltérően ez
     nem jelenik meg magától a webshop beállítófelületén – annak a listának a
     forrása a commerce-core provider registry-je, ebbe a modul nem jelentkezik be.

<div class="mb-1">
    <a class="menu-point collapsed"
       data-toggle="collapse" href="#commerceCouponsCollapse" role="button"
    >
        <span><i class="fas fa-ticket-alt mr-1"></i>Kuponok</span>
        <i class="fa-solid fa-chevron-down"></i>
    </a>
    <div class="collapse collapse-box" id="commerceCouponsCollapse">
        <div class="collapse-menu-points">
            <a href="/webshop/coupons" class="fw-800">
                Kuponok listája <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>
--}}
