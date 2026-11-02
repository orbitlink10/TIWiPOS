<p>Dear {{ $quotation->customer_name }},</p>
<p>Please find attached quotation {{ $quotation->number }} from {{ $quotation->document['issuer_name'] }}.</p>
<p>Valid until {{ $quotation->valid_until->format('d/m/Y') }}.</p>
<p>Thank you for your business.</p>
