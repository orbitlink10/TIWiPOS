<?php

namespace App\Http\Controllers;

use App\Models\QuotationEmail;
use App\Support\Tenant;

class QuotationOutboxController extends Controller
{
    private function emails()
    {
        abort_unless(Tenant::businessId(), 403);

        return QuotationEmail::where('business_id', Tenant::businessId())
            ->where('branch_id', Tenant::branchId());
    }

    public function index()
    {
        return response()->view('quotations.outbox', [
            'emails' => $this->emails()->latest('id')->paginate(20),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(int $email)
    {
        return response()->view('quotations.outbox-show', [
            'email' => $this->emails()->findOrFail($email),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function download(int $email)
    {
        $email = $this->emails()->findOrFail($email);
        abort_unless(isset($email->content['pdf']), 404);

        return response(base64_decode($email->content['pdf']), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$email->content['filename'].'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
