<form method="POST" action="{{ route('purchases.store') }}" id="supply-form" class="np-form">
    @csrf
    <input type="hidden" name="redirect_to" value="purchases.index">
    <div class="np-main">
        <section class="np-card">
            <h2>Supplier & invoice</h2><p class="supplier-muted">Choose a supplier. The invoice / delivery number is generated automatically.</p>
            <div class="np-grid">
                <label class="supplier-field np-wide"><span>Supplier *</span><select name="supplier_id" id="purchase-supplier" required><option value="">Select supplier…</option>@foreach($suppliers as $choice)<option value="{{ $choice->id }}" @selected((string)old('supplier_id',$supplier?->id)===(string)$choice->id)>{{ $choice->name }}{{ $choice->phone ? ' · '.$choice->phone : '' }}</option>@endforeach</select></label>
                <label class="supplier-field"><span>Purchase date *</span><input name="supplied_at" type="date" value="{{ old('supplied_at',today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
                <label class="supplier-field"><span>Invoice / delivery no.</span><input value="Auto-generated on save" readonly aria-describedby="purchase-reference-hint"><small id="purchase-reference-hint" class="np-hint">Assigned when you save this purchase.</small></label>
                <label class="supplier-field"><span>Payment terms</span><select name="terms" id="purchase-terms"><option value="credit" @selected(old('terms','credit')==='credit')>Credit / pay later</option><option value="cash" @selected(old('terms')==='cash')>Fully paid</option></select></label>
                <label class="supplier-field" id="purchase-due-field"><span>Due date *</span><input name="due_at" id="purchase-due" type="date" value="{{ old('due_at') }}"></label>
            </div>
        </section>
        <section class="np-card">
            <div class="np-products-head"><div><h2>Products</h2><p class="np-hint">Supplier-linked products appear first. Selecting a product fills its current cost.</p></div><input type="search" id="purchase-product-filter" placeholder="Find a product…" aria-label="Filter product choices"></div>
            <div class="supplier-scroll"><table class="np-lines"><thead><tr><th>Product *</th><th>Qty *</th><th>Unit cost *</th><th style="text-align:right">Total (KES)</th><th><span aria-label="Remove product"></span></th></tr></thead><tbody id="supply-items">
                @foreach(old('items',[['product_id'=>'','quantity'=>1,'unit_cost'=>'']]) as $index=>$item)
                <tr>
                    <td><select name="items[{{ $index }}][product_id]" class="np-product" required aria-label="Product"><option value="">Select product…</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)($item['product_id']??'')===(string)$product->id)>{{ $product->name }}</option>@endforeach</select><small class="np-hint np-product-code"></small></td>
                    <td><input name="items[{{ $index }}][quantity]" type="number" min="1" max="1000000" step="1" value="{{ $item['quantity']??1 }}" required aria-label="Quantity"></td>
                    <td><input name="items[{{ $index }}][unit_cost]" type="number" min="0" max="99999999.99" step="0.01" value="{{ $item['unit_cost']??'' }}" required aria-label="Unit cost in KES"></td>
                    <td class="np-line-total">0.00</td><td><button type="button" class="np-remove" aria-label="Remove product">&times;</button></td>
                </tr>
                @endforeach
            </tbody></table></div>
            <button type="button" id="add-item" class="supplier-account-button">+ Add product</button>
            @if($products->isEmpty())<p class="np-hint">No active products available. <a href="{{ route('products.create') }}">Add a product</a> to your catalogue first.</p>@endif
        </section>
        <details class="np-card np-notes" @if(old('notes')) open @endif><summary>Notes / delivery details (optional)</summary><textarea name="notes" maxlength="2000" rows="3" placeholder="Additional invoice or delivery information">{{ old('notes') }}</textarea></details>
    </div>
    <aside class="np-card np-summary" aria-label="Purchase totals and payment">
        <div class="np-summary-head"><h2>Purchase total</h2><div class="np-total">KES <span id="supply-total">0.00</span></div><p class="np-hint" id="purchase-unit-count">0 units</p></div>
        <div class="np-summary-fields">
            <label class="supplier-field"><span>Amount paid now (KES)</span><input id="purchase-paid" name="amount" type="number" min="0" step="0.01" value="{{ old('amount','0') }}" required><small class="np-hint">Use 0 for an unpaid credit invoice.</small></label>
            <label class="supplier-field"><span>Payment method</span><select name="method">@foreach(['cash'=>'Cash','mpesa'=>'M-Pesa','bank'=>'Bank','cheque'=>'Cheque','other'=>'Other'] as $key=>$label)<option value="{{ $key }}" @selected(old('method')===$key)>{{ $label }}</option>@endforeach</select></label>
            <label class="supplier-field np-payment-ref"><span>Payment reference</span><input name="payment_reference" maxlength="100" value="{{ old('payment_reference') }}" placeholder="Optional"></label>
        </div>
        <div class="np-balance"><span>Remaining balance</span><strong>KES <span id="purchase-balance">0.00</span></strong></div>
        <p class="np-hint">Save this purchase first, then use Receive purchase to confirm delivered quantities and serial numbers before stock is added.</p>
        <button class="supplier-account-button primary" type="submit">Save purchase</button>
        <a class="supplier-account-button" href="{{ route('purchases.index') }}">Cancel</a>
    </aside>
</form>
