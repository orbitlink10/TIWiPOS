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
@media(max-width:600px) { .sale-card { padding:14px; } .product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .product-tile { min-height:135px; } .field .control { font-size:16px; } .header-row { flex-wrap:wrap; gap:12px; } }
