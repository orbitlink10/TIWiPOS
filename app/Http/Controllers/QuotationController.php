<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Quotation;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    private function quotations()
    {
        abort_unless(Tenant::businessId(), 403);

        return Quotation::query()->where('business_id', Tenant::businessId())
            ->where('branch_id', Tenant::branchId());
    }

    public function index()
    {
        $quotations = $this->quotations()->latest('id')->paginate(20);

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        $this->quotations();
        $business = Business::findOrFail(Tenant::businessId());
        $quotation = new Quotation;
        $document = [
            'issuer_name' => $business->name,
            'issuer_details' => implode("\n", array_filter([$business->phone, $business->billing_email])),
            'currency' => 'KES', 'discount' => '0.00', 'vat_rate' => '0',
            'terms' => "Payment: Payment terms to be agreed before dispatch.\nDelivery: Delivery schedule to be confirmed.\nValidity: Prices and availability are subject to change after the validity date.\nWarranty: Manufacturer warranty applies where applicable.",
            'items' => [['description' => '', 'quantity' => 1, 'unit' => 'PCS', 'unit_price' => '0.00']],
        ];

        return view('quotations.form', compact('quotation', 'document'));
    }

    public function edit(int $quotation)
    {
        $quotation = $this->quotations()->findOrFail($quotation);
        $document = $quotation->document;

        return view('quotations.form', compact('quotation', 'document'));
    }

    public function store(Request $request)
    {
        $this->quotations();
        $quotation = new Quotation(['business_id' => Tenant::businessId(), 'branch_id' => Tenant::branchId()]);

        return $this->save($request, $quotation);
    }

    public function update(Request $request, int $quotation)
    {
        return $this->save($request, $this->quotations()->findOrFail($quotation));
    }

    private function save(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:80', Rule::unique('quotations')->where('business_id', Tenant::businessId())->ignore($quotation->id)],
            'customer_name' => ['required', 'string', 'max:255'],
            'quotation_date' => ['required', 'date_format:Y-m-d'],
            'valid_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:quotation_date'],
            'issuer_name' => ['required', 'string', 'max:255'],
            'issuer_details' => ['nullable', 'string', 'max:2000'],
            'customer_details' => ['nullable', 'string', 'max:2000'],
            'title' => ['nullable', 'string', 'max:255'],
            'incoterms' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', Rule::in(['KES', 'USD', 'UGX', 'TZS', 'EUR', 'GBP'])],
            'discount' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'vat_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'terms' => ['nullable', 'string', 'max:10000'],
            'bank_details' => ['nullable', 'string', 'max:2000'],
            'mpesa_details' => ['nullable', 'string', 'max:1000'],
            'signatory' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:1000000', 'decimal:0,3'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
        ]);
        $subtotal = 0;
        foreach ($data['items'] as &$item) {
            $item['total_cents'] = (int) round((float) $item['quantity'] * round((float) $item['unit_price'] * 100));
            if ($item['total_cents'] > 99999999999999 - $subtotal) {
                throw ValidationException::withMessages(['items' => 'The quotation subtotal cannot exceed 999,999,999,999.99.']);
            }
            $subtotal += $item['total_cents'];
        }
        unset($item);
        $discount = (int) round((float) $data['discount'] * 100);
        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'Discount cannot exceed the subtotal.']);
        }
        $vat = (int) round(($subtotal - $discount) * (float) $data['vat_rate'] / 100);
        $document = collect($data)->except(['number', 'customer_name', 'quotation_date', 'valid_until'])->all();
        $document += ['subtotal_cents' => $subtotal, 'discount_cents' => $discount, 'vat_cents' => $vat, 'total_cents' => $subtotal - $discount + $vat];
        $quotation->fill(collect($data)->only(['number', 'customer_name', 'quotation_date', 'valid_until'])->all());
        $quotation->document = $document;
        $quotation->save();

        return redirect()->route('quotations.show', $quotation)->with('status', 'Quotation saved.');
    }

    public function show(int $quotation)
    {
        $quotation = $this->quotations()->findOrFail($quotation);

        return view('quotations.show', ['quotation' => $quotation, 'document' => $quotation->document]);
    }
}
