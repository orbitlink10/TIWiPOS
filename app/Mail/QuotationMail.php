<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Mail\Mailable;

class QuotationMail extends Mailable
{
    public function __construct(public Quotation $quotation, public string $pdf, public string $filename) {}

    public function build(): static
    {
        return $this->subject('Quotation '.$this->quotation->number)
            ->view('quotations.email')
            ->attachData($this->pdf, $this->filename, ['mime' => 'application/pdf']);
    }
}
