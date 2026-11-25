@php
    $purchaseProducts = $products->map(fn($product) => ['id'=>(string)$product->id, 'name'=>$product->name, 'sku'=>$product->sku, 'barcode'=>$product->barcode, 'serial'=>$product->serial_number, 'cost'=>(float)$product->cost, 'supplier_ids'=>$product->suppliers->pluck('id')->push($product->supplier_id)->filter()->map(fn($id)=>(string)$id)->unique()->values()->all()])->values();
@endphp
<script>
(() => {
    const body = document.getElementById('supply-items');
    if (!body) return;
    const products = @json($purchaseProducts);
    const supplier = document.getElementById('purchase-supplier');
    const filter = document.getElementById('purchase-product-filter');
    const terms = document.getElementById('purchase-terms');
    const due = document.getElementById('purchase-due');
    const paid = document.getElementById('purchase-paid');
    let next = Math.max(-1, ...Array.from(body.querySelectorAll('.np-product')).map(select => Number(select.name.match(/\[(\d+)\]/)[1]))) + 1;
    const money = cents => (cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
    const syncProducts = () => {
        const selects = Array.from(body.querySelectorAll('.np-product'));
        const selectedIds = selects.map(select => select.value).filter(Boolean);
        const term = filter.value.trim().toLowerCase();
        selects.forEach(select => {
            const current = select.value;
            select.replaceChildren(new Option('Select product…', ''));
            const linked = document.createElement('optgroup');
            linked.label = 'Supplier products';
            const others = document.createElement('optgroup');
            others.label = supplier.value ? 'Other catalogue products' : 'Catalogue products';
            products.forEach(product => {
                if (product.id !== current && term && ![product.name, product.sku, product.barcode, product.serial].join(' ').toLowerCase().includes(term)) return;
                const option = new Option(product.name + (product.serial ? ' · '+product.serial : ''), product.id);
                option.disabled = product.id !== current && selectedIds.includes(product.id);
                (supplier.value && product.supplier_ids.includes(supplier.value) ? linked : others).appendChild(option);
            });
            if (linked.children.length) select.appendChild(linked);
            if (others.children.length) select.appendChild(others);
            select.value = current;
            const product = products.find(product => product.id === select.value);
            select.closest('tr').querySelector('.np-product-code').textContent = product?.sku || '';
            select.setCustomValidity(current && selectedIds.filter(id => id === current).length > 1 ? 'This product is already included. Update its quantity instead.' : '');
        });
    };
    const calculate = () => {
        let total = 0, units = 0;
        body.querySelectorAll('tr').forEach(row => {
            const quantity = Math.max(0, Number(row.querySelector('[name$="[quantity]"]').value || 0));
            const cost = Math.round(Math.max(0, Number(row.querySelector('[name$="[unit_cost]"]').value || 0)) * 100);
            const subtotal = quantity * cost;
            total += subtotal;
            if (row.querySelector('.np-product').value) units += quantity;
            row.querySelector('.np-line-total').textContent = money(subtotal);
        });
        const fullyPaid = terms.value === 'cash';
        paid.readOnly = fullyPaid;
        paid.max = (total / 100).toFixed(2);
        if (fullyPaid) paid.value = (total / 100).toFixed(2);
        due.required = !fullyPaid;
        due.disabled = fullyPaid;
        document.getElementById('purchase-due-field').hidden = fullyPaid;
        due.min = document.querySelector('[name=supplied_at]').value;
        document.getElementById('supply-total').textContent = money(total);
        document.getElementById('purchase-unit-count').textContent = units + (units === 1 ? ' unit' : ' units');
        document.getElementById('purchase-balance').textContent = money(Math.max(0, total - Math.round(Number(paid.value || 0) * 100)));
    };
    document.getElementById('add-item').addEventListener('click', () => {
        const row = body.firstElementChild.cloneNode(true);
        row.querySelectorAll('input,select').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${next}]`);
            input.value = input.name.endsWith('[quantity]') ? '1' : '';
            input.setCustomValidity('');
        });
        next++;
        body.appendChild(row);
        syncProducts(); calculate();
        row.querySelector('.np-product').focus();
    });
    body.addEventListener('click', event => {
        const button = event.target.closest('.np-remove');
        if (!button) return;
        const row = button.closest('tr');
        if (body.children.length > 1) row.remove();
        else row.querySelectorAll('input,select').forEach(input => { input.value = input.name.endsWith('[quantity]') ? '1' : ''; input.setCustomValidity(''); });
        syncProducts(); calculate();
    });
    body.addEventListener('change', event => {
        if (event.target.matches('.np-product')) {
            const product = products.find(product => product.id === event.target.value);
            event.target.closest('tr').querySelector('[name$="[unit_cost]"]').value = product ? product.cost.toFixed(2) : '';
            syncProducts();
        }
        calculate();
    });
    body.addEventListener('input', calculate);
    supplier.addEventListener('change', syncProducts);
    filter.addEventListener('input', syncProducts);
    filter.addEventListener('keydown', event => { if (event.key === 'Enter') event.preventDefault(); });
    terms.addEventListener('change', calculate);
    paid.addEventListener('input', calculate);
    document.querySelector('[name=supplied_at]').addEventListener('change', calculate);
    syncProducts(); calculate();
})();
</script>
