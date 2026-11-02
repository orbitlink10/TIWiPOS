<?php

namespace App\Support;

use App\Models\Quotation;
use Dompdf\Dompdf;
use Illuminate\Support\Str;

class QuotationPdf
{
    public function filename(Quotation $quotation): string
    {
        return 'Quotation-'.(Str::slug($quotation->number) ?: $quotation->id).'.pdf';
    }

    public function render(Quotation $quotation): string
    {
        $pdf = new Dompdf(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('quotations.show', [
            'quotation' => $quotation, 'document' => $quotation->document, 'pdf' => true,
        ])->render());
        $pdf->render();

        return $pdf->output();
    }
}
