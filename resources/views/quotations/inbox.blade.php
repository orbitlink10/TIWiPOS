@extends('layouts.app')
@section('title', 'Quotation Inbox')
@section('content')
<h1>Inbox</h1>
<p>Latest 50 emails in your business mailbox. Refresh to check for new messages. Messages remain in your mailbox; opening them here does not mark them as read. Attachments are available in webmail.</p>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px">
<a class="btn" href="{{ route('quotations.index') }}">Back to Quotations</a>
<a class="btn" href="{{ route('quotations.settings.edit') }}">Settings</a>
<a class="btn primary" href="{{ route('quotations.inbox') }}">Refresh Inbox</a>
</div>
@if($error)<p role="alert">{{ $error }}</p>
@elseif($message)
<article style="background:white;padding:24px;border-radius:12px">
<h2>{{ $message['subject'] }}</h2>
<p>From: {{ $message['from'] }}</p><p>{{ $message['date'] }}</p>
<pre style="font:inherit;white-space:pre-wrap;overflow-wrap:anywhere">{{ $message['body'] ?: 'No text preview available. Open this message in webmail.' }}</pre>
</article>
@else
<div style="overflow:auto"><table style="width:100%;border-collapse:collapse;background:white"><thead><tr><th style="text-align:left;padding:12px">From</th><th style="text-align:left;padding:12px">Subject</th><th style="text-align:left;padding:12px">Date</th></tr></thead><tbody>
@forelse($messages as $email)
<tr><td style="padding:12px">{{ $email['from'] }}</td><td style="padding:12px"><a href="{{ route('quotations.inbox.show', $email['uid']) }}">{{ $email['subject'] }}</a>@if($email['unread']) <span>Unread</span>@endif</td><td style="padding:12px">{{ $email['date'] }}</td></tr>
@empty<tr><td colspan="3" style="padding:24px">Your inbox is empty.</td></tr>@endforelse
</tbody></table></div>
@endif
@endsection
