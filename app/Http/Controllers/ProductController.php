<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStock;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withSum(['stocks as stock_on_hand' => function ($q) {
            $q->where('location', 'main');
        }], 'quantity')->latest()->get();

        return view('pages.products', compact('products'));
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $productNames = \App\Models\Product::orderBy('name')->pluck('name');
        return view('pages.product_create', compact('categories', 'suppliers', 'productNames'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:products,serial_number',
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock_alert' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'stock_location' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'serial_number' => $data['serial_number'],
            'barcode' => $data['barcode'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'price' => $data['price'],
            'stock_alert' => $data['stock_alert'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'description' => $data['description'] ?? null,
        ]);

        $initialStock = $data['stock'] ?? 0;
        $stockLocation = $data['stock_location'] ?? 'main';
        ProductStock::updateOrCreate(
            ['product_id' => $product->id, 'location' => $stockLocation],
            ['quantity' => $initialStock]
        );

        return redirect()->route('products')->with('status', 'Product added successfully.');
    }
}
