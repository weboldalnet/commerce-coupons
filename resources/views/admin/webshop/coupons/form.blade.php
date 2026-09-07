{{--
    Kupon szerkesztő.

    Egyetlen űrlap szolgálja ki az összes típust: a közös mezők mindig
    látszanak, a típusfüggő célzás pedig külön dobozban, típus szerint.
    Új kupontípus felvételekor egy új targets/<típus>.blade.php kell – az
    itteni közös rész változatlan marad.

    A típus SOSEM szerkeszthető utólag: a mentett targets szerkezete
    típusfüggő, egy váltás értelmezhetetlen célzást hagyna maga után.
--}}
@extends('admin.layouts.layout')
@section('title', $isEdit ? 'Kupon szerkesztése' : 'Új kupon')

@php
    $tipus = $types[$coupon->type] ?? ['label' => $coupon->type, 'icon' => 'fa-ticket-alt'];
    $kedvezmenyTipus = old('discount_type', $coupon->discount_type ?: 'percent');
    // datetime-local formátum; új kuponnál mától egy hónapig javaslunk
    $kezdes = old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\T00:00'));
    $vege = old('ends_at', optional($coupon->ends_at)->format('Y-m-d\TH:i') ?: now()->addMonth()->format('Y-m-d\T23:59'));
@endphp

@section('content')
    <div class="container mt-lg-4 mt-3 mb-150">
        @includeIf('admin.webshop.partials.alerts')

        <div class="row">
            <div class="col-lg-12">
                <h2 class="header-box my-2">
                    <i class="fa {{ $tipus['icon'] }}"></i>
                    {{ $isEdit ? 'Kupon szerkesztése' : 'Új kupon' }}
                    <span class="fw-400 fs-18">– {{ $tipus['label'] }}</span>
                </h2>
            </div>
        </div>

        <form method="POST"
              action="{{ $isEdit ? route('admin.webshop.coupons.update', $coupon->id) : route('admin.webshop.coupons.store') }}">
            @csrf
            @if($isEdit) @method('PUT') @endif
            <input type="hidden" name="type" value="{{ $coupon->type }}">

            <div class="row">
                {{-- Alapadatok --}}
                <div class="col-lg-6 mb-3">
                    <h2 class="header-box product-info mb-1">Alapadatok</h2>
                    <div class="content-box bordered mb-3">
                        <div class="form-group">
                            <label for="name">Kupon neve <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $coupon->name) }}" required>
                            <span class="text-muted fs-14">Csak az adminban látszik, a vásárló nem látja.</span>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="code">Kuponkód <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code', $coupon->code) }}"
                                   maxlength="64" autocomplete="off" required>
                            <span class="text-muted fs-14">
                                Ezt írja be a vásárló a pénztáron. Mindig nagybetűsen tároljuk, így a
                                beváltásnál nem számít a kis- és nagybetű.
                            </span>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group mb-0">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                       value="1" @if(old('is_active', $isEdit ? $coupon->is_active : true)) checked @endif>
                                <label class="custom-control-label" for="is_active">Aktív</label>
                            </div>
                            <span class="text-muted fs-14">
                                Kikapcsolva a kupon a megadott időszakban sem váltható be.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Kedvezmény --}}
                <div class="col-lg-6 mb-3">
                    <h2 class="header-box product-info mb-1">Kedvezmény</h2>
                    <div class="content-box bordered mb-3">
                        <div class="form-group">
                            <label>Kedvezmény típusa <span class="text-danger">*</span></label>
                            @foreach($discountTypes as $kulcs => $cimke)
                                <div class="custom-control custom-radio">
                                    <input type="radio" class="custom-control-input js-coupon-discount-type"
                                           id="dt-{{ $kulcs }}" name="discount_type" value="{{ $kulcs }}"
                                           @if($kedvezmenyTipus === $kulcs) checked @endif>
                                    <label class="custom-control-label" for="dt-{{ $kulcs }}">{{ $cimke }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="form-group">
                            <label for="discount_value">
                                Mértéke <span class="text-danger">*</span>
                                <span class="text-muted js-coupon-unit"></span>
                            </label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control @error('discount_value') is-invalid @enderror"
                                   id="discount_value" name="discount_value"
                                   value="{{ old('discount_value', $coupon->discount_value) }}" required>
                            @error('discount_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Plafon csak százalékos kuponnál értelmes, ezért JS-sel rejtjük.
                             A szerver is kinullázza fix összegűnél, hogy egy rejtett,
                             ottfelejtett érték se kerülhessen mentésre. --}}
                        <div class="form-group js-coupon-max-discount">
                            <label for="max_discount">Kedvezmény felső határa (Ft)</label>
                            <input type="number" step="1" min="0" class="form-control"
                                   id="max_discount" name="max_discount"
                                   value="{{ old('max_discount', $coupon->max_discount) }}">
                            <span class="text-muted fs-14">
                                Üresen hagyva nincs plafon. Pl. 20%, de legfeljebb 5000 Ft.
                            </span>
                        </div>

                        <div class="form-group mb-0">
                            <label for="min_cart_total">Minimum kosárérték (Ft)</label>
                            <input type="number" step="1" min="0" class="form-control"
                                   id="min_cart_total" name="min_cart_total"
                                   value="{{ old('min_cart_total', $coupon->min_cart_total) }}">
                            <span class="text-muted fs-14">
                                Ez alatti kosárértéknél a kupon nem váltható be. Üresen hagyva nincs alsó határ.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Érvényesség --}}
                <div class="col-lg-6 mb-3">
                    <h2 class="header-box product-info mb-1">Érvényesség</h2>
                    <div class="content-box bordered mb-3">
                        <div class="form-group">
                            <label for="starts_at">Érvényesség kezdete <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror"
                                   id="starts_at" name="starts_at" value="{{ $kezdes }}" required>
                            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group mb-0">
                            <label for="ends_at">Érvényesség vége <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror"
                                   id="ends_at" name="ends_at" value="{{ $vege }}" required>
                            @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Típusfüggő célzás --}}
                <div class="col-lg-6 mb-3">
                    <h2 class="header-box product-info mb-1">Mire vonatkozik</h2>
                    <div class="content-box bordered mb-3">
                        @include('admin.webshop.coupons.targets.' . $coupon->type)
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('admin.webshop.coupons.index') }}" class="btn btn-secondary font-weight-bold">Vissza</a>
                <button type="submit" class="btn btn-primary font-weight-bold ml-2">
                    {{ $isEdit ? 'Mentés' : 'Kupon létrehozása' }}
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            /* A plafon mező csak százalékos kuponnál látszik. Natív JS, mert
               ez a nézet a kupon csomagból jön, és nem függhet attól, hogy a
               befogadó admin oldalon betöltött-e már a jQuery. */
            var tipusMezok = document.querySelectorAll('.js-coupon-discount-type');
            var plafonDoboz = document.querySelector('.js-coupon-max-discount');
            var egysegek = document.querySelectorAll('.js-coupon-unit');

            function frissit() {
                var kivalasztott = document.querySelector('.js-coupon-discount-type:checked');
                var szazalekos = kivalasztott && kivalasztott.value === 'percent';

                if (plafonDoboz) {
                    plafonDoboz.style.display = szazalekos ? '' : 'none';
                }

                Array.prototype.forEach.call(egysegek, function (el) {
                    el.textContent = szazalekos ? '(%)' : '(Ft)';
                });
            }

            Array.prototype.forEach.call(tipusMezok, function (el) {
                el.addEventListener('change', frissit);
            });

            frissit();
        })();
    </script>
@endsection
