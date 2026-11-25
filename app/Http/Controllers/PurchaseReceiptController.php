<?php

namespace App\Http\Controllers;

use App\Models\{Product, ProductStock, PurchaseReceipt, PurchaseReceiptSerial, StockMovement, SupplierInvoice};
use App\Support\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptController extends Controller
{
    private function invoice(int $id)
    {
        abort_unless(auth()->user()->canViewFinancials() && Tenant::businessId() && Tenant::branchId(), 403);
        return SupplierInvoice::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())->whereKey($id);
    }

    public function create(int $invoice)
    {
        $invoice = $this->invoice($invoice)->with(['supplier', 'items.product'])->firstOrFail();
        if ($invoice->stock_received) return redirect()->route('purchases.show', $invoice->id)->with('status', 'This purchase has already been fully received.');
        $products = Product::where('business_id', Tenant::businessId())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        return view('pages.purchases.receive', compact('invoice', 'products'));
    }

    public function store(Request $request, int $invoice)
    {
        $invoiceId = $invoice;
        $invoice = $this->invoice($invoiceId)->firstOrFail();
        $data = $request->validate([
            'receipt_token' => ['required', 'uuid'],
            'received_at' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$invoice->supplied_at->toDateString()],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'lines.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('business_id', Tenant::businessId())],
            'lines.*.serialized' => ['nullable', 'boolean'],
            'lines.*.serial_numbers' => ['nullable', 'string', 'max:100000'],
        ]);

        try {
            DB::transaction(function () use ($data, $invoiceId) {
                $invoice = $this->invoice($invoiceId)->lockForUpdate()->firstOrFail();
                if (PurchaseReceipt::where('supplier_invoice_id', $invoice->id)->where('receipt_token', $data['receipt_token'])->exists()) return;
                if ($invoice->stock_received) throw ValidationException::withMessages(['lines' => 'This purchase is already fully received. Refresh the page.']);
                $items = $invoice->items()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $validIds = $items->keys()->map(fn ($id) => (string) $id)->all();
                foreach (array_keys($data['lines']) as $id) {
                    if (!in_array((string) $id, $validIds, true)) throw ValidationException::withMessages(['lines' => 'A receipt line does not belong to this purchase.']);
                }
                $receipt = $invoice->receipts()->create([
                    'business_id' => $invoice->business_id, 'branch_id' => $invoice->branch_id, 'user_id' => auth()->id(),
                    'receipt_token' => $data['receipt_token'], 'received_at' => $data['received_at'], 'notes' => $data['notes'] ?? null,
                ]);
                $received = 0;
                $seenSerials = [];
                foreach ($data['lines'] as $id => $line) {
                    $item = $items->get($id);
                    $quantity = (int) $line['quantity'];
                    if ($quantity > $item->quantity - $item->received_quantity) {
                        throw ValidationException::withMessages(["lines.{$id}.quantity" => 'Received quantity exceeds the outstanding order for '.$item->product_name.'.']);
                    }
                    $serials = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/u', $line['serial_numbers'] ?? '')), fn ($serial) => $serial !== ''));
                    if ($quantity === 0) {
                        if ($serials) throw ValidationException::withMessages(["lines.{$id}.serial_numbers" => 'Enter a received quantity for these serial numbers.']);
                        continue;
                    }
                    $productId = $item->product_id ?: ($line['product_id'] ?? null);
                    $product = Product::where('business_id', $invoice->business_id)->whereKey($productId)->lockForUpdate()->first();
                    if (!$product) throw ValidationException::withMessages(["lines.{$id}.product_id" => 'Choose a catalogue product for '.$item->product_name.' before receiving stock.']);
                    $serialized = $product->tracks_serials || !empty($line['serialized']) || !empty($product->serial_number) || count($serials) > 0;
                    if ($serialized && count($serials) !== $quantity) {
                        throw ValidationException::withMessages(["lines.{$id}.serial_numbers" => 'Enter exactly '.$quantity.' serial numbers for '.$item->product_name.' (one per received unit).']);
                    }
                    foreach ($serials as $serial) {
                        $normalized = mb_strtolower($serial);
                        if (mb_strlen($serial) > 255 || isset($seenSerials[$normalized])
                            || Product::withoutGlobalScopes()->where('serial_number', $serial)->exists()
                            || PurchaseReceiptSerial::where('serial_number', $serial)->exists()) {
                            throw ValidationException::withMessages(["lines.{$id}.serial_numbers" => 'Serial '.$serial.' is already recorded, repeated, or longer than 255 characters.']);
                        }
                        $seenSerials[$normalized] = true;
                    }
                    $receiptLine = $receipt->lines()->create(['supplier_invoice_item_id' => $item->id, 'product_id' => $product->id, 'quantity' => $quantity]);
                    foreach ($serials as $serial) $receiptLine->serials()->create(['serial_number' => $serial]);
                    if ($serialized && !$product->tracks_serials) $product->update(['tracks_serials' => true]);
                    $stock = ProductStock::firstOrCreate(['business_id' => $invoice->business_id, 'branch_id' => $invoice->branch_id, 'product_id' => $product->id, 'location' => 'main'], ['quantity' => 0]);
                    $stock = ProductStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
                    $before = (int) $stock->quantity;
                    $stock->increment('quantity', $quantity);
                    StockMovement::create([
                        'business_id' => $invoice->business_id, 'branch_id' => $invoice->branch_id, 'product_id' => $product->id, 'user_id' => auth()->id(),
                        'location' => 'main', 'type' => 'purchase', 'quantity_change' => $quantity, 'quantity_before' => $before, 'quantity_after' => $before + $quantity,
                        'reference_type' => 'purchase_receipt', 'reference_id' => $receipt->id, 'note' => 'Received purchase '.$invoice->reference,
                    ]);
                    $item->update(['product_id' => $product->id, 'received_quantity' => $item->received_quantity + $quantity]);
                    $received += $quantity;
                }
                if ($received === 0) throw ValidationException::withMessages(['lines' => 'Enter at least one quantity to receive.']);
                $invoice->update(['stock_received' => $items->every(fn ($item) => $item->received_quantity >= $item->quantity)]);
            }, 3);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23000') throw $exception;
            throw ValidationException::withMessages(['lines' => 'A serial number or receipt was already saved. Refresh and check the receipt history before trying again.']);
        }

        return redirect()->route('purchases.show', $invoiceId)->with('status', 'Purchase received. Confirmed quantities have been added to stock.');
    }
}
