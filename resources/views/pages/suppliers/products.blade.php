<style>
.supplier-map-dialog{width:min(620px,calc(100vw - 24px));max-height:85vh;overflow:auto;padding:22px;border:1px solid #e2e8ee;border-radius:12px;color:#16242b;box-shadow:0 24px 80px rgba(15,23,42,.25)}.supplier-map-dialog::backdrop{background:rgba(15,23,42,.45)}.supplier-map-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.supplier-map-head h2{margin:0;font-size:18px}.supplier-map-dialog .supplier-muted{font-size:12px;line-height:1.6}.supplier-map-dialog input[type=search]{width:100%;padding:11px;border:1px solid #cdd8e3;border-radius:7px;font:inherit;font-size:14px}.supplier-map-tools{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:12px 0}.supplier-map-list{max-height:40vh;overflow-y:auto;border:1px solid #e2e8ee;border-radius:8px}.supplier-map-option{display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #edf0f4;cursor:pointer;font-size:13px}.supplier-map-option:last-child{border-bottom:0}.supplier-map-option input{width:18px;height:18px;accent-color:#1769d1;flex-shrink:0}.supplier-map-option strong{display:block;font-size:13px}.supplier-map-option small{display:block;color:#718087;font-size:11px;margin-top:4px;overflow-wrap:anywhere}.supplier-map-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:18px}.supplier-map-footer span{font-size:12px;color:#718087}.supplier-map-dialog button:disabled{opacity:.5;cursor:default}.supplier-map-dialog [hidden]{display:none!important}.supplier-mapped-sku{overflow-wrap:anywhere;font-size:11px;color:#718087}.supplier-map-empty{padding:18px;font-size:12px;color:#718087;text-align:center}.supplier-map-dialog :is(button,input):focus-visible{outline:2px solid #a7c9f4;outline-offset:2px}
@media(max-width:600px){.supplier-map-dialog{padding:16px}.supplier-map-dialog input[type=search]{font-size:16px}.supplier-map-option{min-height:52px}.supplier-mapped-head{flex-wrap:wrap}.supplier-map-footer{flex-wrap:wrap}}
</style>
<section class="panel" aria-labelledby="supplier-products-title">
    <div class="supplier-history-head supplier-mapped-head">
        <h2 id="supplier-products-title">Supplied products <span class="supplier-history-count">{{ $mappedProducts->count() }}</span></h2>
        @if($supplier->is_active)<button type="button" class="supplier-account-button supplier-no-print" id="open-product-mapping">+ Link products</button>@endif
    </div>
    <p class="supplier-muted">Products linked to this supplier across your business. Each product can be linked to multiple suppliers. Linking a product does not receive stock or create an invoice.</p>
    <div class="supplier-scroll"><table>
        <thead><tr><th scope="col">Product</th><th scope="col">SKU</th><th scope="col">Selling price (KES)</th><th scope="col">Status</th><th scope="col" class="supplier-no-print">Mapping</th></tr></thead>
        <tbody>
            @forelse($mappedProducts as $product)
                <tr><td>{{ $product->name }}@if($product->serial_number)<div class="supplier-muted">{{ $product->serial_number }}</div>@endif</td><td class="supplier-mapped-sku">{{ $product->sku }}</td><td>{{ number_format($product->price, 2) }}</td><td><span class="supplier-badge {{ $product->is_active ? '' : 'inactive' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td><td class="supplier-no-print"><form method="POST" action="{{ route('suppliers.products.destroy', [$supplier->id, $product->id]) }}" onsubmit="return confirm('Unlink this product from the supplier? Its stock and purchase history will remain.');">@csrf @method('DELETE')<button type="submit" class="remove-item">Unlink</button></form></td></tr>
            @empty<tr><td colspan="5" class="supplier-history-empty">No products linked yet. Use Link products to assign existing catalogue items.</td></tr>@endforelse
        </tbody>
    </table></div>
</section>
<dialog id="supplier-product-mapping" class="supplier-map-dialog supplier-no-print" aria-labelledby="supplier-map-title">
    <form method="POST" action="{{ route('suppliers.products.store', $supplier->id) }}">
        @csrf
        <div class="supplier-map-head"><h2 id="supplier-map-title">Link products</h2><button type="button" class="supplier-account-button" data-map-close aria-label="Close product mapping">Close</button></div>
        <p class="supplier-muted">Select products supplied by {{ $supplier->name }}. This list shows active products not yet linked to this supplier. Existing links to other suppliers are kept.</p>
        <input type="search" data-map-search placeholder="Search product name, SKU, barcode or serial…" aria-label="Search products to link">
        <div class="supplier-map-tools"><button type="button" class="supplier-account-button" data-map-select>Select results</button><button type="button" class="supplier-account-button" data-map-clear>Clear selection</button></div>
        <div class="supplier-map-list">
            @foreach($availableProducts as $product)
                <label class="supplier-map-option" data-map-search-text="{{ $product->name.' '.$product->sku.' '.$product->barcode.' '.$product->serial_number }}"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, (array) old('product_ids', [])))><span><strong>{{ $product->name }}</strong><small>{{ $product->sku }}@if($product->serial_number) &middot; {{ $product->serial_number }}@endif</small></span></label>
            @endforeach
            <p class="supplier-map-empty" data-map-empty @if($availableProducts->isNotEmpty()) hidden @endif>No matching products available to link. Products already linked to this supplier are excluded.</p>
        </div>
        <div class="supplier-map-footer"><span data-map-count role="status">0 selected</span><button class="supplier-account-button primary" type="submit" data-map-save disabled>Link selected products</button></div>
    </form>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('supplier-product-mapping');
    const rows = Array.from(dialog.querySelectorAll('[data-map-search-text]'));
    const boxes = rows.map(row => row.querySelector('input[type=checkbox]'));
    const update = () => {
        const count = boxes.filter(box => box.checked).length;
        dialog.querySelector('[data-map-count]').textContent = count + ' selected';
        dialog.querySelector('[data-map-save]').disabled = count === 0 || count > 1000;
    };
    document.getElementById('open-product-mapping')?.addEventListener('click', () => dialog.showModal());
    dialog.querySelector('[data-map-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-map-search]').addEventListener('input', event => {
        const term = event.target.value.toLowerCase().trim();
        rows.forEach(row => { row.hidden = !row.dataset.mapSearchText.toLowerCase().includes(term); });
        dialog.querySelector('[data-map-empty]').hidden = rows.some(row => !row.hidden);
    });
    dialog.querySelector('[data-map-select]').addEventListener('click', () => { rows.filter(row => !row.hidden).forEach(row => { row.querySelector('input').checked = true; }); update(); });
    dialog.querySelector('[data-map-clear]').addEventListener('click', () => { boxes.forEach(box => { box.checked = false; }); update(); });
    boxes.forEach(box => box.addEventListener('change', update));
    update();
    @if($errors->has('product_ids') || $errors->has('product_ids.*')) dialog.showModal(); @endif
})();
</script>
