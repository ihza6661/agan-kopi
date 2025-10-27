@csrf
<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="sku" class="form-label">SKU</label>
        <input id="sku" name="sku" type="text" class="form-control @error('sku') is-invalid @enderror"
            value="{{ old('sku', $product->sku ?? '') }}" maxlength="100" required>
        @error('sku')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nama Produk</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $product->name ?? '') }}" maxlength="255" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="category_id" class="form-label">Kategori</label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror"
            required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $id => $label)
                <option value="{{ $id }}" @selected(old('category_id', $product->category_id ?? '') == $id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-3">
        <label for="price_display" class="form-label">Harga</label>
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input id="price_display" name="price_display" type="text"
                class="form-control @error('price') is-invalid @enderror" inputmode="decimal"
                value="{{ (float) old('price', $product->price ?? 0) == 0 ? '0' : number_format((float) old('price', $product->price ?? 0), 2, ',', '.') }}"
                placeholder="0" autocomplete="off" required>
            <input id="price" name="price" type="hidden" value="{{ old('price', $product->price ?? 0) }}">
            @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 col-md-3">
        <label for="stock" class="form-label">Stok</label>
        <input id="stock" name="stock" type="number" min="0"
            class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', $product->stock ?? 0) }}">
        @error('stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-3">
        <label for="min_stock" class="form-label">Stok Minimal</label>
        <input id="min_stock" name="min_stock" type="number" min="0"
            class="form-control @error('min_stock') is-invalid @enderror"
            value="{{ old('min_stock', $product->min_stock ?? 0) }}">
        @error('min_stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Notifikasi akan muncul jika stok <= minimal.</div>
        </div>

        <div class="col-12 col-md-3">
            <label for="expiry_date" class="form-label">Tanggal Kadaluarsa</label>
            <input id="expiry_date" name="expiry_date" type="date"
                class="form-control @error('expiry_date') is-invalid @enderror"
                value="{{ old('expiry_date', optional($product->expiry_date ?? null)->format('Y-m-d')) }}">
            @error('expiry_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Kosongkan jika tidak berlaku.</div>
        </div>
    </div>

    @push('script')
        <script>
            (function() {
                const priceDisplay = document.getElementById('price_display');
                const priceHidden = document.getElementById('price');
                if (!priceDisplay || !priceHidden) return;

                function normalizeToNumber(str) {
                    if (!str) return '';
                    str = String(str).replace(/[^0-9,]/g, '');
                    const parts = str.split(',');
                    let intPart = parts[0] || '';
                    let decPart = parts[1] || '';
                    intPart = intPart.replace(/^0+(?=\d)/, '');
                    if (decPart.length > 2) decPart = decPart.slice(0, 2);
                    return decPart ? (intPart + '.' + decPart) : intPart;
                }

                function formatRupiahDisplay(str) {
                    if (!str) return '';
                    str = String(str).replace(/[^0-9,]/g, '');
                    const parts = str.split(',');
                    let intPart = parts[0] || '';
                    let decPart = parts[1] || '';
                    intPart = intPart.replace(/^0+(?=\d)/, '');
                    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    if (decPart.length > 0) decPart = decPart.slice(0, 2);
                    return decPart ? (intPart + ',' + decPart) : intPart;
                }

                function syncHidden() {
                    priceHidden.value = normalizeToNumber(priceDisplay.value);
                }

                function onInput() {
                    const pos = priceDisplay.selectionStart;
                    const beforeLen = priceDisplay.value.length;
                    priceDisplay.value = formatRupiahDisplay(priceDisplay.value);
                    const afterLen = priceDisplay.value.length;
                    const delta = afterLen - beforeLen;
                    priceDisplay.setSelectionRange(pos + delta, pos + delta);
                    syncHidden();
                }

                if (priceHidden.value && !priceDisplay.value) {
                    const normalized = String(priceHidden.value).replace(/\./g, ',');
                    priceDisplay.value = formatRupiahDisplay(normalized);
                } else {
                    syncHidden();
                }

                priceDisplay.addEventListener('input', onInput);

                const form = priceDisplay.closest('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        syncHidden();
                    });
                }
            })();
        </script>
    @endpush
