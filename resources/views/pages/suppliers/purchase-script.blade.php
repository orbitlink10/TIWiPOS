<script>
(() => {
const body = document.getElementById('supply-items');
if (!body) return;
let next = Math.max(...Array.from(body.querySelectorAll('input[type=hidden]')).map(el => Number(el.name.match(/\[(\d+)\]/)[1]))) + 1;
const calculate = () => {
let cents = 0;
body.querySelectorAll('tr').forEach(row => { const quantity = row.querySelector('input[name$="[quantity]"]'); const cost = row.querySelector('input[name$="[unit_cost]"]'); cents += Number(quantity.value || 0) * Math.round(Number(cost.value || 0) * 100); });
document.getElementById('supply-total').textContent = (cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
};
document.getElementById('add-item').addEventListener('click', () => {
const row = body.firstElementChild.cloneNode(true);
row.querySelectorAll('input,select').forEach(el => { el.name = el.name.replace(/\[\d+\]/, `[${next}]`); el.value = el.name.endsWith('[quantity]') ? '1' : ''; el.setCustomValidity(''); });
next++; body.appendChild(row); calculate();
});
body.addEventListener('click', event => { if(event.target.classList.contains('remove-item') && body.children.length > 1) { event.target.closest('tr').remove(); calculate(); } });
const options = Array.from(document.querySelectorAll('#supplier-products option'));
const resolveProduct = input => {
const match = options.find(option => option.value.toLowerCase() === input.value.trim().toLowerCase());
input.closest('td').querySelector('input[type=hidden]').value = match ? match.dataset.productId : '';
input.setCustomValidity('');
};
body.addEventListener('input', event => {
if (event.target.matches('.supplier-product-search')) resolveProduct(event.target);
calculate();
});
body.querySelectorAll('.supplier-product-search').forEach(resolveProduct);
calculate();
})();
</script>