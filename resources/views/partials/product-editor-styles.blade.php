<style>
main{min-width:0}
.product-editor{max-width:1080px;background:#fff;border:1px solid #e2e8ee;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(16,36,53,.04)}
.product-editor-head{padding:22px 24px;display:flex;justify-content:space-between;align-items:start;gap:16px;border-bottom:1px solid #edf0f4}
.product-editor h2{margin:4px 0 6px;font-size:21px;letter-spacing:-.4px}.product-editor h3{margin:3px 0 0;font-size:16px}
.product-eyebrow{margin:0;font-size:10px;font-weight:800;letter-spacing:.12em;color:#718087;text-transform:uppercase}
.product-help{margin:5px 0 0;color:#667885;font-size:12px;line-height:1.6}.product-chip{padding:6px 10px;border-radius:6px;background:#eef4fb;color:#1057b0;font-size:11px;font-weight:700;white-space:nowrap}
.product-section{padding:22px 24px}.product-section-head{display:flex;gap:12px;justify-content:space-between;align-items:center;margin-bottom:18px}
.product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.product-field{display:flex;flex-direction:column;gap:6px;min-width:0;color:#334155;font-size:12px;font-weight:700}.product-field.wide{grid-column:span 2}.product-field.full{grid-column:1/-1}
.product-field input,.product-field select,.product-field textarea{width:100%;min-width:0;padding:11px 12px;border:1px solid #cdd8e3;border-radius:7px;background:#fff;color:#16242b;font:inherit;font-size:14px;font-weight:500}.product-field textarea{resize:vertical}
.product-field input:focus,.product-field select:focus,.product-field textarea:focus{outline:2px solid #a7c9f4;outline-offset:1px;border-color:#1769d1}.product-field [aria-invalid="true"]{border-color:#c53929}
.product-field small{color:#718087;font-size:11px;font-weight:500;line-height:1.5}.product-field .field-error{color:#b42318}.product-field a{color:#1057b0}.product-required{color:#b42318}
.product-optional{border-top:1px solid #edf0f4}.product-optional summary{padding:18px 24px;cursor:pointer;color:#334155;font-size:13px;font-weight:700}.product-optional summary span{color:#718087;font-size:12px;font-weight:500;margin-left:8px}.product-optional .product-section{padding-top:4px}
.product-stock{border-top:1px solid #edf0f4;background:#fafbfd}.product-active{display:flex;gap:10px;align-items:start;margin-top:18px;font-size:13px;font-weight:700;color:#334155}.product-active input{width:18px;height:18px;margin:1px 0 0;accent-color:#1769d1}.product-active small{display:block;margin-top:3px;color:#718087;font-size:11px;font-weight:500}
.product-editor-footer{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 24px;border-top:1px solid #e2e8ee}.product-actions{display:flex;gap:8px;flex-shrink:0}
.product-button{display:inline-block;padding:11px 16px;border:1px solid #ccd8e5;border-radius:7px;background:#fff;color:#334155;text-decoration:none;font:inherit;font-size:13px;font-weight:700;cursor:pointer}.product-button.primary{border-color:#1769d1;background:#1769d1;color:#fff}
.product-message{max-width:1080px;padding:12px 16px;border-radius:8px;margin-bottom:14px;font-size:13px;line-height:1.6}.product-message.error{color:#b42318;background:#fff0ee;border:1px solid #f1ccc6}.product-message.success{color:#166534;background:#edf8ef;border:1px solid #c7e7ce}
@media(max-width:900px){.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.product-section-head{display:block}}
@media(max-width:600px){.product-grid{grid-template-columns:minmax(0,1fr);gap:14px}.product-field.wide{grid-column:auto}.product-editor-head,.product-section,.product-editor-footer{padding:18px 16px}.product-optional summary{padding:18px 16px}.product-optional summary span{display:block;margin:6px 0 0}.product-editor-footer{align-items:stretch;flex-direction:column}.product-actions{justify-content:flex-end}.product-field input,.product-field select,.product-field textarea{font-size:16px}.product-chip{display:none}}
</style>