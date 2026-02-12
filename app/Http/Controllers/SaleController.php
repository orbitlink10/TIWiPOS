<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Support\Tenant;

class SaleController extends Controller
{
    private const TAX_RATE = 0.16;

    protected $lastSaleId;

    protected function ensureAdmin(): void
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Only admins can perform this action.');
        }
    }

    public function index(Request $request)
    {
        $branchId = Tenant::branchId();
        $query = Sale::with(['items.product', 'payments', 'user'])
            ->orderByDesc('created_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('q')) {
            $term = $request->get('q');
            $query->where(function ($q) use ($term) {
                $q->where('sale_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->get('date'));
        }

        $sales = $query->paginate(15)->withQueryString();

        return view('pages.sales_index', compact('sales'));
    }

    public function create()
    {
        $branchId = Tenant::branchId();

        $products = Product::withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
            $q->where('location', 'main');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
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
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'method' => 'required|in:cash,card,bank,mobile,other',
            'apply_tax' => 'nullable|boolean',
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
            $available = $product->stockOnHand('main', Tenant::branchId());
            if ($qty > $available) {
                return back()->withErrors(['items' => 'Not enough stock for ' . $product->name . ' (available: ' . $available . ').'])->withInput();
            }
        }

        $saleNumber = 'S' . now()->format('YmdHis');
        $subtotal = 0;
        $lineItems = [];

        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            $unitPrice = array_key_exists('unit_price', $item)
                ? (float) $item['unit_price']
                : (float) $product->price;
            $unitPrice = round($unitPrice, 2);
            $lineSubtotal = $unitPrice * $item['quantity'];
            $subtotal += $lineSubtotal;
            $lineItems[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
            ];
        }

        $tax = $request->boolean('apply_tax')
            ? round($subtotal * self::TAX_RATE, 2)
            : 0.0;
        $total = $subtotal + $tax;

        DB::transaction(function () use ($data, $saleNumber, $subtotal, $tax, $total, $lineItems) {
            $sale = Sale::create([
                'branch_id' => Tenant::branchId(),
                'sale_number' => $saleNumber,
                'customer_id' => null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_location' => $data['customer_location'] ?? null,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $total,
                'payment_status' => 'paid',
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            foreach ($lineItems as $line) {
                SaleItem::create([
                    'business_id' => $sale->business_id,
                    'branch_id' => $sale->branch_id,
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ]);

                ProductStock::where('product_id', $line['product']->id)
                    ->where('location', 'main')
                    ->when(Tenant::branchId(), function ($q, $branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->decrement('quantity', $line['quantity']);

                StockMovement::create([
                    'business_id' => $sale->business_id,
                    'branch_id' => Tenant::branchId(),
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
                'business_id' => $sale->business_id,
                'branch_id' => Tenant::branchId(),
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

    public function edit(Sale $sale)
    {
        $this->ensureAdmin();
        $branchId = $sale->branch_id ?? Tenant::branchId();

        $products = Product::withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
            $q->where('location', 'main');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        }], 'quantity')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $sale->load(['items.product', 'payments']);

        return view('pages.sale_edit', compact('sale', 'products'));
    }

    public function update(Request $request, Sale $sale)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'method' => 'required|in:cash,card,bank,mobile,other',
            'apply_tax' => 'nullable|boolean',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_location' => 'nullable|string|max:255',
        ]);

        $branchId = $sale->branch_id ?? Tenant::branchId();

        DB::transaction(function () use ($data, $sale, $branchId, $request) {
            $sale->load('items');

            // Restock previous items before recalculating
            foreach ($sale->items as $item) {
                $stock = ProductStock::firstOrCreate(
                    ['product_id' => $item->product_id, 'location' => 'main', 'branch_id' => $branchId],
                    ['quantity' => 0, 'business_id' => auth()->user()->business_id]
                );
                $stock->increment('quantity', $item->quantity);
            }

            // Validate availability with restored stock
            $grouped = [];
            foreach ($data['items'] as $item) {
                $grouped[$item['product_id']] = ($grouped[$item['product_id']] ?? 0) + $item['quantity'];
            }

            foreach ($grouped as $productId => $qty) {
                $product = Product::with('stocks')->findOrFail($productId);
                $available = $product->stockOnHand('main', $branchId);
                if ($qty > $available) {
                    throw ValidationException::withMessages(['items' => 'Not enough stock for ' . $product->name . ' (available: ' . $available . ').']);
                }
            }

            // Remove old rows
            SaleItem::where('sale_id', $sale->id)->delete();
            StockMovement::where('reference_type', 'sale')->where('reference_id', $sale->id)->delete();

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

            $tax = $request->boolean('apply_tax')
                ? round($subtotal * self::TAX_RATE, 2)
                : 0.0;
            $total = $subtotal + $tax;

            foreach ($lineItems as $line) {
                SaleItem::create([
                    'business_id' => $sale->business_id,
                    'branch_id' => $branchId,
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ]);

                $stock = ProductStock::where('product_id', $line['product']->id)
                    ->where('location', 'main')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->first();

                if (!$stock || $stock->quantity < $line['quantity']) {
                    throw ValidationException::withMessages(['items' => 'Stock fell below required quantity while editing.']);
                }

                $stock->decrement('quantity', $line['quantity']);

                StockMovement::create([
                    'business_id' => $sale->business_id,
                    'branch_id' => $branchId,
                    'product_id' => $line['product']->id,
                    'user_id' => auth()->id(),
                    'location' => 'main',
                    'type' => 'sale',
                    'quantity_change' => -1 * $line['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'note' => 'Sale #' . $sale->sale_number . ' edited',
                ]);
            }

            $sale->update([
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_location' => $data['customer_location'] ?? null,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $total,
                'payment_status' => 'paid',
                'status' => 'completed',
                'paid_at' => now(),
                'branch_id' => $branchId,
            ]);

            $payment = $sale->payments()->first();
            if ($payment) {
                $payment->update([
                    'method' => $data['method'],
                    'amount' => $total,
                    'branch_id' => $branchId,
                    'paid_at' => now(),
                ]);
            } else {
                Payment::create([
                    'business_id' => $sale->business_id,
                    'branch_id' => $branchId,
                    'sale_id' => $sale->id,
                    'method' => $data['method'],
                    'amount' => $total,
                    'reference' => null,
                    'paid_at' => now(),
                ]);
            }
        });

        return redirect()->route('sale.receipt', $sale->id)->with('status', 'Sale updated.');
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'payments']);
        return view('pages.receipt', compact('sale'));
    }
}
