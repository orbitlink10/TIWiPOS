<?php

namespace App\Support;

use App\Models\Quotation;
use Dompdf\Dompdf;
use Illuminate\Support\Str;

class QuotationPdf
{
    public static function logoData(?string $path): ?string
    {
        if (!$path || !str_starts_with($path, 'quotation-logos/') || str_contains($path, '..')) {
            return null;
        }
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        if (!$disk->exists($path)) { return null; }
        $mime = $disk->mimeType($path);
        if (!in_array($mime, ['image/png', 'image/jpeg'], true)) { return null; }
        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }

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
