<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\CashierShift;
use App\Support\Tenant;

class SaleController extends Controller
{
    private const TAX_RATE = 0.16;
    private const PRIMARY_LOCATION = 'main';
    private static ?bool $saleItemsHasSerialNumber = null;
    private static ?bool $saleItemsHasUnitCost = null;

    protected $lastSaleId;

    private function visibleSales(Builder $query, string $table = 'sales'): Builder
    {
        $user = auth()->user();

        if ($user && !$user->canViewAllSales()) {
            $query->where($table . '.user_id', $user->id);
        }

        return $query;
    }

    private function ensureSaleIsVisible(Sale $sale): void
    {
        if (!auth()->user()->canAccessSaleRecord($sale)) {
            abort(404);
        }
    }

    protected function ensureAdmin(): void
    {
        if (!auth()->user()->canAccessAbility('edit_sales')) {
            abort(403, 'Only admins can perform this action.');
        }
    }

    private function saleItemsHasSerialNumberColumn(): bool
    {
        if (self::$saleItemsHasSerialNumber !== null) {
            return self::$saleItemsHasSerialNumber;
        }

        return self::$saleItemsHasSerialNumber = Schema::hasColumn('sale_items', 'serial_number');
    }

    private function saleItemsHasUnitCostColumn(): bool
    {
        if (self::$saleItemsHasUnitCost !== null) {
            return self::$saleItemsHasUnitCost;
        }

        return self::$saleItemsHasUnitCost = Schema::hasColumn('sale_items', 'unit_cost');
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
        $canViewProfit = auth()->user()->canViewProfit();
        $showingOwnSalesOnly = !auth()->user()->canViewAllSales();
        $query = $this->visibleSales(Sale::withoutGlobalScope('branch'))
            ->with([
                'items' => function ($query) {
                    $query->withoutGlobalScope('branch')->with('product');
                },
                'payments' => function ($query) {
                    $query->withoutGlobalScope('branch');
                },
                'user',
            ])
            ->orderByDesc('created_at');

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

        if ($canViewProfit) {
            $sales->getCollection()->transform(function (Sale $sale) {
                $profit = $sale->items->sum(function (SaleItem $item) {
                    $qty = (float) $item->quantity;
                    $subtotal = (float) $item->subtotal;
                    $unitCost = $item->unit_cost !== null
                        ? (float) $item->unit_cost
                        : (float) ($item->product->cost ?? 0);

                    return $subtotal - ($unitCost * $qty);
                });

                $sale->setAttribute('profit_total', round((float) $profit, 2));

                return $sale;
            });
        }

        return view('pages.sales_index', compact('sales', 'canViewProfit', 'showingOwnSalesOnly'));
    }

    public function create()
    {
        $branchId = Tenant::branchId();
        $cashierShift = CashierShift::currentQuery()->first();
        if (!$cashierShift) return redirect()->route('shifts.index')->with('status', 'Open your shift before making a sale.');

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
            ->where('price', '>', 0)
            ->orderBy('name')
            ->get();

        $categories = \App\Models\Category::query()
            ->whereIn('id', $products->pluck('category_id')->filter()->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.sale', compact('products', 'categories', 'cashierShift'));
    }

    private function paymentRules(): array
    {
        return [
            'method' => 'required_without:payments|in:cash,card,bank,mobile,other',
            'split_payment' => 'nullable|boolean',
            'payments' => 'nullable|array:cash,card,bank,mobile,other',
            'payments.*' => 'nullable|numeric|min:0|max:999999999.99|decimal:0,2',
        ];
    }

    private function resolvePayments(array $data, float $total): array
    {
        if (empty($data['split_payment']) && !array_key_exists('payments', $data)) {
            return [$data['method'] => round($total, 2)];
        }

        $payments = array_filter($data['payments'] ?? [], fn ($amount) => (float) $amount > 0);
        if (count($payments) < 2) {
            throw ValidationException::withMessages(['payments' => 'Enter amounts for at least two payment methods.']);
        }
        $paidCents = array_sum(array_map(fn ($amount) => (int) round((float) $amount * 100), $payments));
        if ($paidCents !== (int) round($total * 100)) {
            throw ValidationException::withMessages(['payments' => 'Payment amounts must add up exactly to the sale total.']);
        }

        return array_map(fn ($amount) => round((float) $amount, 2), $payments);
    }

    private function savePayments(Sale $sale, array $payments, ?int $branchId): void
    {
        foreach ($payments as $method => $amount) {
            $payment = $sale->payments()->withoutGlobalScope('branch')->firstOrNew(['method' => $method]);
            $payment->fill([
                'business_id' => $sale->business_id,
                'branch_id' => $branchId,
                'amount' => $amount,
            ]);
            if (!$payment->exists) {
                $payment->paid_at = $sale->paid_at ?? $sale->created_at ?? now();
            }
            $payment->save();
        }
        $sale->payments()->withoutGlobalScope('branch')->whereNotIn('method', array_keys($payments))->delete();
    }

    public function store(Request $request)
    {
        $businessId = Tenant::businessId();

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            ...$this->paymentRules(),
            'apply_tax' => 'nullable|boolean',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_location' => 'nullable|string|max:255',
            'technician_name' => 'nullable|string|max:255',
            'technician_phone' => 'nullable|string|max:50',
            'installation_location' => 'nullable|string|max:255',
            'installation_amount' => 'nullable|numeric|min:0',
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

        $saleNumber = 'S' . Str::ulid();
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
                'unit_cost' => round((float) $product->cost, 2),
                'subtotal' => $lineSubtotal,
            ];
        }

        $installationAmount = round((float) ($data['installation_amount'] ?? 0), 2);
        $tax = $request->boolean('apply_tax')
            ? round($subtotal * self::TAX_RATE, 2)
            : 0.0;
        $total = $subtotal + $tax + $installationAmount;

        $payments = $this->resolvePayments($data, $total);

        DB::transaction(function () use ($data, $saleNumber, $subtotal, $tax, $total, $installationAmount, $lineItems, $branchId, $payments) {
            $shift = CashierShift::currentQuery()->lockForUpdate()->first();
            if (!$shift) throw ValidationException::withMessages(['shift' => 'Your shift is closed. Open a new shift before completing this sale.']);
            $sale = Sale::create([
                'branch_id' => $branchId,
                'cashier_shift_id' => $shift->id,
                'sale_number' => $saleNumber,
                'customer_id' => null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_location' => $data['customer_location'] ?? null,
                'technician_name' => $data['technician_name'] ?? null,
                'technician_phone' => $data['technician_phone'] ?? null,
                'installation_location' => $data['installation_location'] ?? null,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'installation_amount' => $installationAmount,
                'total' => $total,
                'payment_status' => 'paid',
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            foreach ($lineItems as $line) {
                $payload = [
                    'business_id' => $sale->business_id,
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ];

                if ($this->saleItemsHasSerialNumberColumn()) {
                    $payload['serial_number'] = $line['product']->serial_number;
                }
                if ($this->saleItemsHasUnitCostColumn()) {
                    $payload['unit_cost'] = $line['unit_cost'];
                }

                SaleItem::create($payload);

                $this->consumeStockForSale(
                    $line['product'],
                    (int) $line['quantity'],
                    $branchId,
                    $sale,
                    'POS sale ' . $saleNumber
                );
            }

            $this->savePayments($sale, $payments, $branchId);

            $this->lastSaleId = $sale->id;
        });

        return redirect()->route('sale.receipt', $this->lastSaleId)->with('status', 'Sale completed.');
    }

    public function edit(Sale $sale)
    {
        $this->ensureSaleIsVisible($sale);
        $this->ensureAdmin();
        if ($sale->cashier_shift_id && CashierShift::whereKey($sale->cashier_shift_id)->where('status', 'closed')->exists()) {
            abort(403, 'Sales from a closed shift cannot be edited.');
        }
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
        $this->ensureSaleIsVisible($sale);
        $this->ensureAdmin();
        $businessId = Tenant::businessId();

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.sale_item_id' => [
                'nullable', 'integer',
                Rule::exists('sale_items', 'id')->where(fn ($query) => $query->where('sale_id', $sale->id)->where('business_id', $businessId)),
            ],
            ...$this->paymentRules(),
            'apply_tax' => 'nullable|boolean',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_location' => 'nullable|string|max:255',
            'technician_name' => 'nullable|string|max:255',
            'technician_phone' => 'nullable|string|max:50',
            'installation_location' => 'nullable|string|max:255',
            'installation_amount' => 'nullable|numeric|min:0',
        ]);

        $branchId = $sale->branch_id ?? Tenant::branchId();

        DB::transaction(function () use ($data, $sale, $branchId, $request) {
            if ($sale->cashier_shift_id) {
                $shift = CashierShift::where('business_id', $sale->business_id)->where('branch_id', $sale->branch_id)
                    ->whereKey($sale->cashier_shift_id)->lockForUpdate()->firstOrFail();
                if ($shift->status !== 'open') throw ValidationException::withMessages(['shift' => 'Sales from a closed shift cannot be edited.']);
            }
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
                $priorItem = null;
                if (!empty($item['sale_item_id'])) {
                    $priorItem = $sale->items->firstWhere('id', $item['sale_item_id']);
                    if (!$priorItem || (int) $priorItem->product_id !== (int) $product->id) {
                        throw ValidationException::withMessages(['items' => 'The original sale item does not match this product.']);
                    }
                } elseif (!array_key_exists('sale_item_id', $item)) {
                    // Older clients omit row IDs. Preserve an unambiguous recorded price.
                    $priorRows = $sale->items->where('product_id', $product->id);
                    if ($priorRows->pluck('unit_price')->unique()->count() > 1) {
                        throw ValidationException::withMessages(['items' => 'Reload this sale to preserve its different recorded unit prices.']);
                    }
                    $priorItem = $priorRows->first();
                }
                $unitPrice = round((float) ($priorItem?->unit_price ?? $product->price), 2);
                $lineSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $lineSubtotal;
                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'unit_cost' => round((float) ($priorItem?->unit_cost ?? $product->cost), 2),
                    'subtotal' => $lineSubtotal,
                ];
            }

            $installationAmount = round((float) ($data['installation_amount'] ?? $sale->installation_amount ?? 0), 2);
            $tax = $request->boolean('apply_tax')
                ? round($subtotal * self::TAX_RATE, 2)
                : 0.0;
            $total = $subtotal + $tax + $installationAmount;
            $payments = $this->resolvePayments($data, $total);

            foreach ($lineItems as $line) {
                $payload = [
                    'business_id' => $sale->business_id,
                    'sale_id' => $sale->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => 0,
                    'subtotal' => $line['subtotal'],
                ];

                if ($this->saleItemsHasSerialNumberColumn()) {
                    $payload['serial_number'] = $line['product']->serial_number;
                }
                if ($this->saleItemsHasUnitCostColumn()) {
                    $payload['unit_cost'] = $line['unit_cost'];
                }

                SaleItem::create($payload);

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
                'technician_name' => $data['technician_name'] ?? $sale->technician_name,
                'technician_phone' => $data['technician_phone'] ?? $sale->technician_phone,
                'installation_location' => $data['installation_location'] ?? $sale->installation_location,
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $tax,
                'installation_amount' => $installationAmount,
                'total' => $total,
                'payment_status' => 'paid',
                'status' => 'completed',
                'branch_id' => $branchId,
            ]);

            $this->savePayments($sale, $payments, $branchId);
        });

        return redirect()->route('sale.receipt', $sale->id)->with('status', 'Sale updated.');
    }

    public function receipt($sale)
    {
        $sale = Sale::withoutGlobalScope('branch')
            ->with([
                'items' => function ($query) {
                    $query->withoutGlobalScope('branch')->with('product');
                },
                'payments' => function ($query) {
                    $query->withoutGlobalScope('branch');
                },
            ])
            ->whereKey($sale)
            ->firstOrFail();

        $this->ensureSaleIsVisible($sale);

        return view('pages.receipt', compact('sale'));
    }
}
