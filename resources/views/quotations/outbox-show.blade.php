@extends('layouts.app')
@section('title', 'Outbox Email')
@section('content')
<div class="card" style="padding:24px">
    <a class="btn" href="{{ route('quotations.outbox') }}">Back to Outbox</a>
    <h1>{{ $email->subject }}</h1>
    <p>From: {{ $email->sender }}</p>
    <p>To: {{ $email->recipient }}</p>
    <p>Status: {{ ucfirst($email->status) }}</p>
    <p>Attempted: {{ $email->created_at->timezone('Africa/Nairobi')->format('d/m/Y H:i') }} EAT</p>
    @if($email->sent_at)<p>Sent: {{ $email->sent_at->timezone('Africa/Nairobi')->format('d/m/Y H:i') }} EAT</p>@endif
    @if($email->status === 'failed')
        <p role="alert">This attempt failed. Check Email Settings and the server logs before trying again.</p>
    @elseif($email->status === 'recorded')
        <p>This email was recorded locally only. Configure SMTP in Quotation Settings to send emails.</p>
    @elseif($email->status === 'sending')
        <p>Sending started, but no outcome has been recorded yet.</p>
    @else
        <p>Accepted by the mail server. Delivery to the recipient is not confirmed.</p>
    @endif
    <pre style="font:inherit;white-space:pre-wrap;overflow-wrap:anywhere">{{ $email->content['body'] ?? 'No email preview available.' }}</pre>
    @if(isset($email->content['pdf']))
        <a class="btn" href="{{ route('quotations.outbox.download', $email) }}">Download original PDF</a>
    @endif
    <a class="btn" href="{{ route('quotations.show', $email->quotation_id) }}">Open Quotation</a>
</div>
@endsection
