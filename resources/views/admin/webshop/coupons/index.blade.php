@extends('admin.layouts.layout')
@section('title', 'Kuponok')

@section('content')
    <div class="container mt-lg-4 mt-3 mb-150">
        @includeIf('admin.webshop.partials.alerts')

        <div class="row">
            <div class="col-lg-12">
                <h2 class="header-box my-2"><i class="fa fa-ticket-alt"></i> Kuponok</h2>
            </div>
        </div>

        {{-- Létrehozó kártyák típusonként.
             A lista a Coupon::TYPES konstansból jön, így egy új kupontípus
             felvétele után ez a nézet változtatás nélkül megjeleníti. --}}
        <div class="row">
            @foreach($types as $kulcs => $tipus)
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="content-box bordered h-100 d-flex flex-column text-center">
                        <div class="fs-30 mb-2"><i class="fa {{ $tipus['icon'] }}"></i></div>
                        <h3 class="fw-600 fs-18 mb-1">{{ $tipus['label'] }}</h3>
                        <p class="text-muted fs-14 flex-grow-1">{{ $tipus['description'] }}</p>
                        <a href="{{ route('admin.webshop.coupons.create', $kulcs) }}"
                           class="btn btn-primary fw-600">
                            <i class="fa fa-plus-circle"></i> Létrehozás
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <h2 class="header-box my-2">Meglévő kuponok</h2>

        <div class="content-box bordered mb-3">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Név</th>
                        <th>Kód</th>
                        <th>Típus</th>
                        <th>Kedvezmény</th>
                        <th>Érvényesség</th>
                        <th>Aktív</th>
                        <th class="text-right"><i class="fa fa-pen"></i></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($coupons as $coupon)
                        @php($lejart = $coupon->ends_at && $coupon->ends_at->isPast())
                        <tr>
                            <td class="font-weight-bold">{{ $coupon->name }}</td>
                            <td><code>{{ $coupon->code }}</code></td>
                            <td>{{ $types[$coupon->type]['label'] ?? $coupon->type }}</td>
                            <td class="ws-nowrap">
                                @if($coupon->isPercent())
                                    {{ rtrim(rtrim(number_format($coupon->discount_value, 2, ',', ' '), '0'), ',') }}%
                                    @if($coupon->max_discount)
                                        <span class="text-muted fs-14">
                                            (max. {{ number_format($coupon->max_discount, 0, ',', ' ') }} Ft)
                                        </span>
                                    @endif
                                @else
                                    {{ number_format($coupon->discount_value, 0, ',', ' ') }} Ft
                                @endif
                            </td>
                            <td class="ws-nowrap @if($lejart) text-danger @endif">
                                {{ optional($coupon->starts_at)->format('Y.m.d.') }} –
                                {{ optional($coupon->ends_at)->format('Y.m.d.') }}
                                @if($lejart)
                                    <i class="fa fa-exclamation-triangle" title="Lejárt"></i>
                                @endif
                            </td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input js-toggle-active"
                                           id="active{{ $coupon->id }}"
                                           data-id="{{ $coupon->id }}"
                                           data-url="{{ route('admin.webshop.coupons.toggle-active') }}"
                                           @if($coupon->is_active) checked @endif>
                                    <label class="custom-control-label" for="active{{ $coupon->id }}"></label>
                                </div>
                            </td>
                            <td class="text-right ws-nowrap">
                                <a href="{{ route('admin.webshop.coupons.edit', $coupon->id) }}"
                                   class="btn btn-sm btn-primary"><i class="fa fa-pen"></i></a>
                                <button type="button" class="btn btn-sm btn-danger js-delete-btn"
                                        data-url="{{ route('admin.webshop.coupons.destroy', $coupon->id) }}">
                                    <i class="fa fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Még nincs létrehozott kupon.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @includeIf('admin.webshop.modals.delete-confirm')

    <script src="/packages/webshop/admin/js/webshop-admin.js"></script>
    <script>
        // A megosztott webshop admin segédek. Ha a webshop csomag nincs
        // telepítve, a kupon lista attól még megjelenik – csak a kapcsoló
        // és a törlés-megerősítés nem működik, ezért a létezést nézzük.
        if (window.WebshopAdmin) {
            WebshopAdmin.initToggleActive();
            WebshopAdmin.initDeleteConfirm();
        }
    </script>
@endsection
