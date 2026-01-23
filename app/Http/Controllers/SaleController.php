<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\ProductStock;
use App\Models\StockMovement;

class SaleController extends Controller
{
    protected $lastSaleId;

    public function create()
    {
        $products = Product::withSum(['stocks as stock_on_hand' => function ($q) {
            $q->where('location', 'main');
        }], 'quantity')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pages.sale', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'method' => 'required|in:cash,card,bank,mobile,other',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_location' => 'nullable|string|max:255',
        ]);

        // Check stock availability per product (summed quantities)
        $grouped = [];
        foreach ($data['items'] as $item) {
            $grouped[$item['product_id']] = ($grouped[$item['product_id']] ?? 0) + $item['quantity'];
        }

        foreach ($grouped as $productId => $qty) {
            $product = Product::with('stocks')->findOrFail($productId);
            $available = $product->stockOnHand();
            if ($qty > $available) {
                return back()->withErrors(['items' => 'Not enough stock for ' . $product->name . ' (available: ' . $available . ').'])->withInput();
            }
        }

        $saleNumber = 'S' . now()->format('YmdHis');
        $subtotal = 0;
        $lineItems = [];

        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            $unitPrice = $product->price;
            $lineSubtotal = $unitPrice * $item['quantity'];
            $subtotal += $lineSubtotal;
            $lineItems[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        $total = $subtotal;

        DB::transaction(function () use ($data, $saleNumber, $subtotal, $total, $lineItems) {
            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'customer_id' => null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_location' => $data['customer_location'] ?? null,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $total,
                'payment_status' => 'paid',
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            foreach ($lineItems as $line) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ]);

                ProductStock::where('product_id', $line['product']->id)
                    ->where('location', 'main')
                    ->decrement('quantity', $line['quantity']);

                StockMovement::create([
                    'product_id' => $line['product']->id,
                    'user_id' => auth()->id(),
                    'location' => 'main',
                    'type' => 'sale',
                    'quantity_change' => -1 * $line['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'note' => 'POS sale ' . $saleNumber,
                ]);
            }

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $data['method'],
                'amount' => $total,
                'reference' => null,
                'paid_at' => now(),
            ]);

            $this->lastSaleId = $sale->id;
        });

        return redirect()->route('sale.receipt', $this->lastSaleId)->with('status', 'Sale completed.');
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'payments']);
        return view('pages.receipt', compact('sale'));
    }
}
