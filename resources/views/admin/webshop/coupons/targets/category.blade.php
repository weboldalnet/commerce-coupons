{{-- Kategória kupon: pontosan egy kategória. --}}
@if(empty($categoryOptions))
    <div class="alert alert-warning mb-0">
        Nincs elérhető kategórialista. Kategória kupon csak akkor menthető, ha a
        webshop csomag telepítve van és van legalább egy kategória.
    </div>
@else
    @php($valasztott = (int) old('category_id', $coupon->categoryId()))
    <div class="form-group mb-2">
        <label for="category_id">Kategória <span class="text-danger">*</span></label>
        <select class="form-control" id="category_id" name="category_id" required>
            <option value="">– válassz kategóriát –</option>
            @foreach($categoryOptions as $id => $nev)
                <option value="{{ $id }}" @if($valasztott === (int) $id) selected @endif>{{ $nev }}</option>
            @endforeach
        </select>
    </div>
    <span class="text-muted fs-14">
        A kedvezmény a kosárban lévő, ebbe a kategóriába tartozó termékek összegére vonatkozik.
    </span>
@endif
