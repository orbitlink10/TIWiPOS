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
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'method' => 'required|in:cash,card,bank,mobile,other',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_location' => 'nullable|string|max:255',
        ]);

        $product = Product::with('stocks')->findOrFail($data['product_id']);
        $available = $product->stockOnHand();
        if ($data['quantity'] > $available) {
            return back()->withErrors(['quantity' => 'Not enough stock (available: ' . $available . ').'])->withInput();
        }

        $saleNumber = 'S' . now()->format('YmdHis');
        $unitPrice = $product->price;
        $subtotal = $unitPrice * $data['quantity'];
        $total = $subtotal;

        DB::transaction(function () use ($data, $product, $saleNumber, $unitPrice, $subtotal, $total) {
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

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'unit_price' => $unitPrice,
                'discount' => 0,
                'subtotal' => $subtotal,
            ]);

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $data['method'],
                'amount' => $total,
                'reference' => null,
                'paid_at' => now(),
            ]);

            ProductStock::where('product_id', $product->id)
                ->where('location', 'main')
                ->decrement('quantity', $data['quantity']);

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'location' => 'main',
                'type' => 'sale',
                'quantity_change' => -1 * $data['quantity'],
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'note' => 'POS sale ' . $saleNumber,
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
