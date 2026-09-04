{{-- Kupon modul admin menüpontja.

     Csomagváz: egyelőre nincs admin felülete a modulnak, ezért a menüpont
     ki van kommentelve – így a `commerce-coupons:extend --view=sidebar` nem tesz
     törött linket az admin oldalsávba. A modul fejlesztésekor kell aktiválni.

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
