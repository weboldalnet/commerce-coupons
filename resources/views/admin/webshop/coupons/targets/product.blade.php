{{--
    Termék kupon: egyenként kiválasztott termékek.

    A kereső a webshop csomag admin.webshop.products.search végpontját hívja,
    de csak ha az létezik – a kupon modul enélkül is telepíthető, ilyenkor
    a doboz egy figyelmeztetést mutat a néma hiba helyett.

    Saját, natív JS-sel írt picker, nem a WebshopAdmin.initProductRelationPicker:
    az fixen a js-related-* osztályokra és a related_product_ids[] névre van
    drótozva, újrahasználva ütközne a kuponűrlap mezőivel.
--}}
@php($keresoUtvonal = Route::has('admin.webshop.products.search') ? route('admin.webshop.products.search') : null)

@if(!$keresoUtvonal)
    <div class="alert alert-warning mb-0">
        Nincs elérhető termékkereső. Termék kupon csak akkor menthető, ha a
        webshop csomag telepítve van.
    </div>
@else
    <div class="form-group mb-2">
        <label for="coupon-product-search">Kedvezményes termékek <span class="text-danger">*</span></label>

        <div class="position-relative mb-2">
            <input type="text" class="form-control" id="coupon-product-search"
                   data-url="{{ $keresoUtvonal }}"
                   placeholder="Kezdj gépelni a termék nevéből vagy cikkszámából…"
                   autocomplete="off">
            <div class="list-group position-absolute w-100 d-none" id="coupon-product-results"
                 style="z-index: 20; max-height: 320px; overflow-y: auto;"></div>
        </div>

        <div id="coupon-product-list">
            @foreach($selectedProducts as $termek)
                <div class="d-flex align-items-center border rounded p-2 mb-1">
                    <span class="mr-auto @if($termek['missing']) text-danger @endif">
                        @if($termek['missing'])
                            Törölt termék (#{{ $termek['id'] }})
                        @else
                            {{ $termek['name'] }}
                            @if($termek['sku'])
                                <span class="text-muted fs-14">({{ $termek['sku'] }})</span>
                            @endif
                        @endif
                    </span>
                    <input type="hidden" name="product_ids[]" value="{{ $termek['id'] }}">
                    <button type="button" class="btn btn-sm btn-outline-danger js-coupon-product-remove">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            @endforeach
        </div>

        <span class="text-muted fs-14">
            A kedvezmény a kosárban lévő, itt felsorolt termékek összegére vonatkozik.
        </span>
    </div>

    <script>
        (function () {
            var mezo = document.getElementById('coupon-product-search');
            var talalatok = document.getElementById('coupon-product-results');
            var lista = document.getElementById('coupon-product-list');
            var idozito = null;

            if (!mezo || !talalatok || !lista) {
                return;
            }

            function szovegElem(szoveg) {
                var span = document.createElement('span');
                span.textContent = szoveg;
                return span;
            }

            function benneVan(id) {
                var mezok = lista.querySelectorAll('input[name="product_ids[]"]');
                for (var i = 0; i < mezok.length; i++) {
                    if (parseInt(mezok[i].value, 10) === id) {
                        return true;
                    }
                }
                return false;
            }

            function hozzaad(termek) {
                var sor = document.createElement('div');
                sor.className = 'd-flex align-items-center border rounded p-2 mb-1';

                var nev = szovegElem(termek.name + (termek.sku ? ' (' + termek.sku + ')' : ''));
                nev.className = 'mr-auto';
                sor.appendChild(nev);

                var rejtett = document.createElement('input');
                rejtett.type = 'hidden';
                rejtett.name = 'product_ids[]';
                rejtett.value = termek.id;
                sor.appendChild(rejtett);

                var torol = document.createElement('button');
                torol.type = 'button';
                torol.className = 'btn btn-sm btn-outline-danger js-coupon-product-remove';
                torol.innerHTML = '<i class="fa fa-times"></i>';
                sor.appendChild(torol);

                lista.appendChild(sor);
            }

            function talalatokElrejt() {
                talalatok.classList.add('d-none');
                talalatok.innerHTML = '';
            }

            mezo.addEventListener('input', function () {
                var kifejezes = mezo.value.trim();

                clearTimeout(idozito);

                if (kifejezes.length < 2) {
                    talalatokElrejt();
                    return;
                }

                idozito = setTimeout(function () {
                    fetch(mezo.dataset.url + '?q=' + encodeURIComponent(kifejezes), {
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        credentials: 'same-origin'
                    })
                        .then(function (valasz) { return valasz.json(); })
                        .then(function (termekek) {
                            talalatok.innerHTML = '';

                            if (!termekek || !termekek.length) {
                                var ures = szovegElem('Nincs találat.');
                                ures.className = 'list-group-item text-muted';
                                talalatok.appendChild(ures);
                                talalatok.classList.remove('d-none');
                                return;
                            }

                            termekek.forEach(function (termek) {
                                var gomb = document.createElement('button');
                                gomb.type = 'button';
                                gomb.className = 'list-group-item list-group-item-action text-left';
                                gomb.textContent = termek.name + (termek.sku ? ' (' + termek.sku + ')' : '');

                                gomb.addEventListener('click', function () {
                                    if (!benneVan(parseInt(termek.id, 10))) {
                                        hozzaad(termek);
                                    }
                                    mezo.value = '';
                                    talalatokElrejt();
                                });

                                talalatok.appendChild(gomb);
                            });

                            talalatok.classList.remove('d-none');
                        })
                        .catch(function () {
                            talalatokElrejt();
                        });
                }, 250);
            });

            // Eseménydelegálás: az utólag hozzáadott sorok törlése is működjön
            lista.addEventListener('click', function (esemeny) {
                var gomb = esemeny.target.closest('.js-coupon-product-remove');
                if (gomb) {
                    gomb.parentNode.remove();
                }
            });

            document.addEventListener('click', function (esemeny) {
                if (!esemeny.target.closest('#coupon-product-search, #coupon-product-results')) {
                    talalatokElrejt();
                }
            });
        })();
    </script>
@endif
