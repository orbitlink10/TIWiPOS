.sale-shell { --sale-accent:#1769d1; --sale-accent-strong:#1057b0; --sale-accent-soft:#eef4fb; font-family:inherit; }
.sale-layout { grid-template-columns:minmax(0,1fr) minmax(380px, .85fr); gap:16px; }
.sale-main { min-width:0; }
.sale-card { background:#fff; border:1px solid #e2e8ee; border-radius:12px; padding:18px; box-shadow:0 1px 2px rgba(16,36,53,.04); }
.sale-card h2,.sale-card h3 { font-size:18px; letter-spacing:-.3px; }
.sale-summary-btn,.add-btn,.complete-btn { background:var(--sale-accent); box-shadow:none; border:0; }
.sale-summary-btn { background:#1769d1; }
.category-pills { flex-wrap:nowrap; overflow-x:auto; padding-bottom:6px; }
.category-pill { flex-shrink:0; border-radius:6px; box-shadow:none!important; }
.field .control { border:1px solid #cdd8e3; border-radius:7px; font-family:inherit; font-size:14px; }
.sale-shell :is(button,input,select,summary):focus-visible { outline:3px solid #8cbaf2; outline-offset:2px; }
.switch input:focus-visible + .switch-slider,.payment-chip input:focus-visible + span { outline:3px solid #8cbaf2; outline-offset:2px; }
.product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(145px,1fr)); gap:10px; margin-top:16px; max-height:52vh; overflow-y:auto; padding:3px; }
.product-tile { display:flex; flex-direction:column; gap:8px; min-height:140px; padding:14px; text-align:left; background:#fff; border:1px solid #e2e8ee; border-radius:9px; color:#16242b; font:inherit; cursor:pointer; }
.product-tile:hover { border-color:#1769d1; background:#f5f9ff; }
.product-tile strong { font-size:13px; overflow-wrap:anywhere; }
.product-tile-code { color:#718087; font-size:11px; }
.product-tile-price { margin-top:auto; color:#1057b0; font-weight:800; font-variant-numeric:tabular-nums; }
.product-tile-stock { color:#667885; font-size:11px; }
.product-tile:disabled { opacity:.55; cursor:default; }
.sale-shell [hidden] { display:none!important; }
.manual-entry { margin-top:18px; border-top:1px solid #edf0f4; padding-top:14px; }
.manual-entry summary,.installation summary { cursor:pointer; font-size:13px; font-weight:800; color:#334155; }
.installation summary span { float:right; color:#718087; font-size:11px; font-weight:500; }
.checkout { position:sticky; top:16px; min-width:0; }
.checkout-head { gap:10px; }
.cart-count { margin-left:auto; white-space:nowrap; }
.clear-cart { border:0; background:none; color:#b42318; font:inherit; font-size:12px; cursor:pointer; padding:8px; }
.catalogue-empty { padding:24px 12px; text-align:center; color:#718087; font-size:13px; line-height:1.7; }
.cart-table-wrap { margin-top:14px; border-radius:7px; max-height:38vh; overflow:auto; }
.cart-table { min-width:440px; table-layout:auto; }
.line-qty-input { min-width:58px; }
.line-price-input { min-width:76px; }
.cart-table th { position:sticky; top:0; background:#f5f7fa; }
.complete-btn:disabled,.clear-cart:disabled { opacity:.5; cursor:default; transform:none; }
.complete-btn { min-height:48px; }
.total-line.total strong { color:#1057b0; font-variant-numeric:tabular-nums; }
.payment-chip input:checked + span { border-color:#1769d1; background:#eef4fb; color:#1057b0; box-shadow:none; }
@media(max-width:1250px) { .sale-layout { grid-template-columns:minmax(0,1fr); } .checkout { position:static; } .product-grid { max-height:420px; } }
/* Baseline terminal: action rail, current sale, product rows. */
body:has(.sale-shell) .layout { grid-template-columns:220px minmax(0,1fr); }
body:has(.sale-shell) main { min-width:0; padding:20px; }
body:has(.sale-shell) .header-row h1 { font-size:24px; }
.sale-layout { grid-template-columns:88px minmax(340px,.95fr) minmax(390px,1.2fr); gap:12px; align-items:start; }
.sale-main { grid-column:3; grid-row:1; gap:10px; }
.sale-card { border-color:#d6e1eb; border-radius:7px; padding:14px; }
.sale-card h2,.sale-card h3 { font-size:17px; }
.sale-summary-btn,.add-btn,.complete-btn { border-radius:6px; }
.sale-summary-btn { font-size:13px; padding:10px 14px; }
.terminal-actions { display:grid; gap:8px; position:sticky; top:20px; }
.terminal-actions button,.terminal-actions a { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:7px; min-height:74px; padding:8px 4px; border:1px solid #ccd8e5; border-radius:6px; background:#fff; color:#19344e; font-family:inherit; font-size:11px; font-weight:600; text-align:center; text-decoration:none; cursor:pointer; }
.terminal-actions span { display:grid; place-items:center; width:26px; height:24px; border-radius:5px; background:#edf4fc; color:#1769d1; font-size:10px; font-weight:800; }
.terminal-actions button:hover,.terminal-actions a:hover { background:#eaf2ff; border-color:#1769d1; }
.catalogue-heading { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.terminal-caption { margin:0; color:#718087; font-size:11px; }
.sale-lead { font-size:12px; }
.category-pill { border-radius:5px; padding:6px 10px; }
.category-pill:hover { color:#1057b0; }
.field .control { height:40px; border-radius:5px; font-size:13px; }
.sale-shell a:focus-visible { outline:3px solid #8cbaf2; outline-offset:2px; }
.product-grid { display:block; margin-top:14px; max-height:calc(100dvh - 315px); padding:0; overflow:auto; border:1px solid #d6e1eb; border-radius:6px; background:#fff; }
.product-list-head,.product-tile { display:grid; grid-template-columns:minmax(0,1fr) 38px 82px 48px 62px; gap:6px; align-items:center; padding:9px 8px; }
.product-list-head { position:sticky; top:0; z-index:1; background:#f0f4f8; color:#536477; font-size:9px; font-weight:800; text-transform:uppercase; }
.product-list-head span:not(:first-child) { text-align:right; }
.product-tile { min-height:0; min-width:0; border:0; border-top:1px solid #d6e1eb; border-radius:0; cursor:default; }
.product-tile:hover { background:#f8fbff; }
.product-tile.in-cart { background:#eef8f4; }
.product-tile.is-out { opacity:.55; }
.product-row-name { min-width:0; }
.product-row-name strong { display:block; font-size:12px; line-height:1.4; overflow-wrap:anywhere; }
.product-tile-code { display:block; margin-top:3px; font-size:10px; }
.product-tile-price { margin:0; font-size:12px; text-align:right; }
.product-tile-stock { font-size:12px; text-align:right; }
.product-row-qty { min-width:0; width:100%; height:34px; padding:4px; border:1px solid #cdd8e3; border-radius:4px; font-family:inherit; font-size:13px; font-weight:700; text-align:center; }
.product-row-add { height:34px; padding:0 6px; border:1px solid #1769d1; border-radius:4px; background:#1769d1; color:#fff; font-family:inherit; font-size:11px; font-weight:700; cursor:pointer; white-space:nowrap; }
.product-row-add:disabled { cursor:default; }
.manual-entry { margin-top:14px; padding-top:12px; }
.manual-entry summary,.installation summary,.customer-details summary { cursor:pointer; font-size:12px; font-weight:800; color:#334155; }
.installation summary span,.customer-details summary span { float:right; color:#718087; font-size:10px; font-weight:500; }
.checkout { grid-column:2; grid-row:1; top:20px; }
.checkout-head { gap:8px; margin-bottom:12px; }
.cart-count { font-size:10px; padding:4px 8px; }
.clear-cart { font-size:11px; padding:6px; }
.catalogue-empty { padding:28px 12px; font-size:12px; }
.cart-table-wrap { margin-top:12px; border-radius:5px; max-height:36vh; box-shadow:none; }
.cart-table { min-width:310px; }
.cart-table th { padding:8px 5px; font-size:9px; }
.cart-table td { padding:9px 5px; }
.cart-table :is(th,td):first-child { width:auto; }
.line-item-name { font-size:11px; overflow-wrap:anywhere; }
.line-qty-input { min-width:42px; padding:0 3px; }
.line-price-input { min-width:66px; padding:0 4px; }
.line-edit { border-radius:4px; font-size:11px; }
.remove-btn { min-width:0; padding:0 4px; border:0; background:transparent; border-radius:4px; font-size:10px; }
.customer-details { margin-top:14px; padding:12px 0; border-bottom:1px solid #e2e8ee; }
#open_payment { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:0 12px; font-size:12px; }
.terminal-hint { margin:9px 0 0; color:#718087; font-size:10px; text-align:center; line-height:1.6; }
.payment-dialog { width:min(480px,calc(100vw - 32px)); max-height:calc(100dvh - 32px); overflow:auto; padding:24px; border:1px solid #d6e1eb; border-radius:12px; color:#16242b; background:#fff; box-shadow:0 24px 80px #10243533; }
.payment-dialog::backdrop { background:rgba(11,31,63,.5); }
.payment-dialog-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:24px; }
.payment-dialog-head h3 { margin-top:5px; font-size:22px; }
.payment-close { width:36px; height:36px; border:1px solid #d6e1eb; border-radius:6px; background:#f5f8fc; color:#334155; font-size:24px; cursor:pointer; }
.payment-amount { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 0; border-top:1px solid #e2e8ee; border-bottom:1px solid #e2e8ee; }
.payment-amount strong { color:#1057b0; font-size:26px; }
.payment-dialog .checkout-label { margin-top:24px; }
.payment-dialog .complete-btn { margin-top:24px; }
.payment-chip span { min-height:52px; border-radius:6px; font-size:13px; }
@media(max-width:1350px) {
    body:has(.sale-shell) .layout { grid-template-columns:190px minmax(0,1fr); }
    body:has(.sale-shell) main { padding:16px; }
    .sale-layout { grid-template-columns:minmax(310px,.9fr) minmax(360px,1.1fr); }
    .terminal-actions { grid-column:1/-1; position:static; display:flex; gap:6px; }
    .terminal-actions button,.terminal-actions a { flex:1; flex-direction:row; min-height:42px; gap:6px; }
    .checkout { grid-column:1; grid-row:2; }
    .sale-main { grid-column:2; grid-row:2; }
    .product-grid { max-height:calc(100dvh - 370px); }
}
@media(max-width:1000px) {
    .sale-layout { grid-template-columns:minmax(0,1fr); }
    .sale-main { grid-column:1; grid-row:2; }
    .checkout { grid-column:1; grid-row:3; position:static; }
    .product-grid { max-height:380px; }
}
@media(max-width:900px) { body:has(.sale-shell) .layout { grid-template-columns:minmax(0,1fr); } }
@media(max-width:600px) {
    body:has(.sale-shell) main { padding:12px; }
    .sale-card { padding:12px; }
    .terminal-actions { flex-wrap:wrap; }
    .terminal-actions button,.terminal-actions a { flex:1 1 28%; min-height:44px; }
    .product-list-head { display:none; }
    .product-tile { grid-template-columns:minmax(0,1fr) 60px 70px; grid-template-areas:'name name price' 'stock qty add'; gap:8px; padding:10px; }
    .product-row-name { grid-area:name; }
    .product-tile-price { grid-area:price; }
    .product-tile-stock { grid-area:stock; text-align:left; }
    .product-tile-stock::before { content:'Stock: '; }
    .product-row-qty { grid-area:qty; }
    .product-row-add { grid-area:add; }
    .product-row-qty,.product-row-add { height:44px; font-size:16px; }
    .field .control { height:44px; font-size:16px; }
    .cart-table-wrap { max-height:50vh; }
    .sale-shell .cart-table { min-width:0; }
    .cart-table thead { display:none; }
    .cart-table tbody tr { display:grid; grid-template-columns:50px 76px minmax(0,1fr) 46px; gap:6px; padding:10px 6px; border-top:1px solid #edf2f8; align-items:end; }
    .cart-table tbody tr:first-child { border-top:0; }
    .cart-table td { display:block; width:auto!important; border:0; padding:0; }
    .cart-table td:first-child { grid-column:1/-1; }
    .cart-table td:nth-child(2)::before,.cart-table td:nth-child(3)::before,.cart-table td:nth-child(4)::before { display:block; margin-bottom:4px; color:#718087; font-size:9px; text-align:right; }
    .cart-table td:nth-child(2)::before { content:'Qty'; }
    .cart-table td:nth-child(3)::before { content:'Price'; }
    .cart-table td:nth-child(4)::before { content:'Subtotal'; }
    .cart-table .line-edit { height:40px; font-size:13px; }
    .cart-table .remove-btn { height:40px; }
    .header-row { flex-wrap:wrap; gap:12px; }
    .payment-dialog { padding:18px; }
    .payment-amount strong { font-size:22px; }
}
