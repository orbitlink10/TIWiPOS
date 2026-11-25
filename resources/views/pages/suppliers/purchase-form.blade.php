
<p class="supplier-muted">Enter one invoice or delivery note. Choose existing products or type a product name. The reference must be unique for this supplier.</p>
<form method="POST" action="{{ route('suppliers.supply', $supplier->id) }}" id="supply-form">@csrf<input type="hidden" name="redirect_to" value="purchases.index">
<div class="supplier-grid">
<label class="supplier-field">Invoice / delivery reference<input name="reference" value="{{ old('reference') }}" required maxlength="100"></label>
<label class="supplier-field">Supply date<input name="supplied_at" type="date" value="{{ old('supplied_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
<label class="supplier-field">Terms<select name="terms"><option value="credit" @selected(old('terms') === 'credit')>Credit</option><option value="cash" @selected(old('terms') === 'cash')>Cash (fully paid)</option></select></label>
<label class="supplier-field">Due date (required for credit)<input name="due_at" type="date" value="{{ old('due_at') }}"></label>
</div>
<div class="supplier-scroll"><table><thead><tr><th>Product / serial</th><th>Quantity</th><th>Unit cost (KES)</th><th></th></tr></thead><tbody id="supply-items">
@foreach(old('items', [['product_id' => '', 'quantity' => 1, 'unit_cost' => '']]) as $index => $item)
<tr><td style="min-width:240px">@php($selectedProduct = $products->firstWhere('id', $item['product_id'] ?? null))
<input type="text" name="items[{{ $index }}][product_search]" class="supplier-product-search" list="supplier-products" placeholder="Type product name or serial" aria-label="Product name or serial" autocomplete="off" maxlength="255" value="{{ $item['product_search'] ?? ($selectedProduct ? $selectedProduct->name . ($selectedProduct->serial_number ? ' - ' . $selectedProduct->serial_number : '') . ' (' . $selectedProduct->sku . ')' : '') }}" required>
<input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] ?? '' }}"></td><td><input name="items[{{ $index }}][quantity]" type="number" min="1" max="1000000" value="{{ $item['quantity'] ?? 1 }}" required></td><td><input name="items[{{ $index }}][unit_cost]" type="number" min="0" max="99999999.99" step="0.01" value="{{ $item['unit_cost'] ?? '' }}" required></td><td><button type="button" class="remove-item">Remove</button></td></tr>
@endforeach
</tbody></table></div>
<datalist id="supplier-products">@foreach($products as $product)<option value="{{ $product->name }}{{ $product->serial_number ? ' - ' . $product->serial_number : '' }} ({{ $product->sku }})" data-product-id="{{ $product->id }}"></option>@endforeach</datalist>
<div class="supplier-actions" style="margin:12px 0"><button type="button" id="add-item" class="btn">Add product row</button><strong>Invoice total: KES <span id="supply-total">0.00</span></strong></div>
<div class="supplier-grid">
<label class="supplier-field">Amount paid now (0 if unpaid)<input name="amount" type="number" min="0" step="0.01" value="{{ old('amount', '0') }}" required></label>
<label class="supplier-field">Payment method<select name="method">@foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank', 'cheque' => 'Cheque', 'other' => 'Other'] as $key => $label)<option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>@endforeach</select></label>
<label class="supplier-field">Payment reference<input name="payment_reference" maxlength="100" value="{{ old('payment_reference') }}"></label>
</div>
<label class="supplier-field">Notes / delivery details<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
@if(auth()->user()->canAccessAbility('add_stock'))
<p><label><input type="checkbox" name="receive_stock" value="1" @checked(old('receive_stock'))> Add these quantities to the selected branch's main stock</label><br><span class="supplier-muted">Stock additions require a registered product in every row. Leave unchecked to save typed names or if stock was already entered. Saving with this option increases stock immediately.</span></p>
@endif
<button class="btn" style="margin-top:12px">Save supplier invoice</button>
</form>