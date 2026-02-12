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
    private const PRIMARY_LOCATION = 'main';

    protected $lastSaleId;

    protected function ensureAdmin(): void
    {
        if (auth()->user()->role !== 'owner') {
            abort(403, 'Only admins can perform this action.');
        }
    }

    private function consumeStockForSale(Product $product, int $quantity, ?int $branchId, Sale $sale, string $note): void
    {
        $stocks = ProductStock::query()
            ->where('product_id', $product->id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByRaw("CASE WHEN location = '" . self::PRIMARY_LOCATION . "' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $available = (int) $stocks->sum(fn($stock) => max(0, (int) $stock->quantity));
        if ($available < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'Not enough stock for ' . $product->name . ' (available: ' . $available . ').',
            ]);
        }

        $remaining = $quantity;
        foreach ($stocks as $stock) {
            $onHand = (int) $stock->quantity;
            if ($onHand <= 0) {
                continue;
            }

            $take = min($remaining, $onHand);
            $before = $onHand;
            $after = $before - $take;

            $stock->update(['quantity' => $after]);

            StockMovement::create([
                'business_id' => $sale->business_id,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'location' => $stock->location,
                'type' => 'sale',
                'quantity_change' => -1 * $take,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'note' => $note,
            ]);

            $remaining -= $take;
            if ($remaining <= 0) {
                break;
            }
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

        $products = Product::query()
            ->select('id', 'name', 'sku', 'barcode', 'serial_number', 'price', 'category_id')
            ->withSum(['stocks as stock_on_hand' => function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            }], 'quantity')
            ->whereHas('stocks', function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
                $q->where('quantity', '>', 0);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = \App\Models\Category::query()
            ->whereIn('id', $products->pluck('category_id')->filter()->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.sale', compact('products', 'categories'));
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

        $branchId = Tenant::branchId();

        // Check stock availability per product.
        $grouped = [];
        foreach ($data['items'] as $item) {
            $grouped[$item['product_id']] = ($grouped[$item['product_id']] ?? 0) + $item['quantity'];
        }

        foreach ($grouped as $productId => $qty) {
            $product = Product::with('stocks')->findOrFail($productId);
            $available = $product->stockOnHand(null, $branchId);
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

        DB::transaction(function () use ($data, $saleNumber, $subtotal, $tax, $total, $lineItems, $branchId) {
            $sale = Sale::create([
                'branch_id' => $branchId,
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
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'serial_number' => $line['product']->serial_number,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ]);

                $this->consumeStockForSale(
                    $line['product'],
                    (int) $line['quantity'],
                    $branchId,
                    $sale,
                    'POS sale ' . $saleNumber
                );
            }

            Payment::create([
                'business_id' => $sale->business_id,
                'branch_id' => $branchId,
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

            // Restock prior deducted quantities before recalculating.
            $priorMovements = StockMovement::query()
                ->where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->lockForUpdate()
                ->get();

            if ($priorMovements->isNotEmpty()) {
                foreach ($priorMovements as $movement) {
                    if ((int) $movement->quantity_change >= 0) {
                        continue;
                    }

                    $qty = abs((int) $movement->quantity_change);
                    if ($qty === 0) {
                        continue;
                    }

                    $stock = ProductStock::firstOrCreate(
                        [
                            'product_id' => $movement->product_id,
                            'location' => $movement->location ?: self::PRIMARY_LOCATION,
                            'branch_id' => $branchId,
                        ],
                        ['quantity' => 0, 'business_id' => $sale->business_id]
                    );
                    $stock->increment('quantity', $qty);
                }
            } else {
                foreach ($sale->items as $item) {
                    $stock = ProductStock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'location' => self::PRIMARY_LOCATION,
                            'branch_id' => $branchId,
                        ],
                        ['quantity' => 0, 'business_id' => $sale->business_id]
                    );
                    $stock->increment('quantity', $item->quantity);
                }
            }

            // Validate availability with restored stock
            $grouped = [];
            foreach ($data['items'] as $item) {
                $grouped[$item['product_id']] = ($grouped[$item['product_id']] ?? 0) + $item['quantity'];
            }

            foreach ($grouped as $productId => $qty) {
                $product = Product::with('stocks')->findOrFail($productId);
                $available = $product->stockOnHand(null, $branchId);
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
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'serial_number' => $line['product']->serial_number,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ]);

                $this->consumeStockForSale(
                    $line['product'],
                    (int) $line['quantity'],
                    $branchId,
                    $sale,
                    'Sale #' . $sale->sale_number . ' edited'
                );
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
