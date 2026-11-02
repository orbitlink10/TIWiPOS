<?php
namespace App\Http\Controllers;
use App\Models\Business;
use App\Support\Tenant;
use Illuminate\Http\Request;
class QuotationSettingsController extends Controller
{
    private function business(): Business
    {
        abort_unless(Tenant::businessId(), 403);
        return Business::findOrFail(Tenant::businessId());
    }
    public function edit()
    {
        $business = $this->business();
        return view('quotations.settings', ['defaults' => $business->quotation_defaults ?? [], 'business' => $business]);
    }
    public function updateIncoming(Request $request)
    {
        $business = $this->business();
        $data = $request->validate([
            'incoming_host' => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9.-]+$/'],
            'incoming_username' => ['required', 'email', 'max:254'],
            'incoming_password' => ['nullable', 'string', 'max:1000'],
        ]);
        $mail = $business->quotation_mail ?? [];
        $password = $data['incoming_password'] ?? ($mail['incoming']['password'] ?? null);
        if (!$password) { throw \Illuminate\Validation\ValidationException::withMessages(['incoming_password' => 'Enter the incoming mailbox password.']); }
        $mail['incoming'] = ['host' => $data['incoming_host'], 'username' => $data['incoming_username'], 'password' => $password];
        $business->quotation_mail = $mail;
        $business->save();
        return redirect()->route('quotations.settings.edit')->with('status', 'Incoming email settings saved. Open Inbox to check the connection.');
    }

    public function updateEmail(Request $request)
    {
        $business = $this->business();
        $data = $request->validate([
            'from_address' => ['required', 'email', 'max:254'],
            'from_name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:253', 'regex:/^[a-zA-Z0-9.-]+$/'],
            'port' => ['required', 'integer', 'in:465,587'],
            'username' => ['required', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['password'] = $data['mail_password'] ?? ($business->quotation_mail['password'] ?? null);
        unset($data['mail_password']);
        if (!$data['password']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['mail_password' => 'Enter the mailbox password or app password.']);
        }
        if (isset($business->quotation_mail['incoming'])) { $data['incoming'] = $business->quotation_mail['incoming']; }
        $business->quotation_mail = $data;
        $business->save();
        return redirect()->route('quotations.settings.edit')->with('status', 'Quotation email settings saved.');
    }

    public function update(Request $request)
    {
        $business = $this->business();
        $data = $request->validate([
            'issuer_name' => ['required', 'string', 'max:255'],
            'issuer_details' => ['nullable', 'string', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:255'],
            'signatory' => ['nullable', 'string', 'max:255'],
            'terms' => ['nullable', 'string', 'max:10000'],
            'bank_details' => ['nullable', 'string', 'max:2000'],
            'mpesa_details' => ['nullable', 'string', 'max:1000'],
            'currency' => ['required', 'in:KES,USD,UGX,TZS,EUR,GBP'],
            'vat_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'incoterms' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);
        unset($data['logo'], $data['remove_logo']);
        $data['logo_path'] = $business->quotation_defaults['logo_path'] ?? null;
        if ($request->boolean('remove_logo')) { $data['logo_path'] = null; }
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('quotation-logos/'.$business->id, 'local');
        }
        $business->quotation_defaults = $data;
        $business->save();
        return redirect()->route('quotations.settings.edit')->with('status', 'Quotation defaults saved.');
    }
}
