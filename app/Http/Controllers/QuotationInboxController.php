<?php
namespace App\Http\Controllers;
use App\Models\Business;
use App\Support\Tenant;
use App\Support\QuotationInbox;
class QuotationInboxController extends Controller
{
    public function index(QuotationInbox $inbox, ?int $message = null)
    {
        abort_unless(Tenant::businessId(), 403);
        $business = Business::findOrFail(Tenant::businessId());
        $settings = $business->quotation_mail['incoming'] ?? null;
        $data = ['messages' => [], 'message' => null, 'error' => null];
        if (!$settings) { $data['error'] = 'Configure Incoming Email in Quotation Settings first.'; }
        else {
            try { $data = array_replace($data, $inbox->read($settings, $message)); }
            catch (\RuntimeException $exception) { $data['error'] = $exception->getMessage(); }
            if ($message !== null && !$data['error']) { abort_unless($data['message'], 404); }
        }
        return response()->view('quotations.inbox', $data)->header('Cache-Control', 'private, no-store');
    }
}
