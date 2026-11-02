@extends('layouts.app')
@section('title', 'Quotation Outbox')
@section('content')
<div class="card" style="padding:24px">
    <h1>Outbox</h1>
    <p>Quotation emails from this branch. History starts when Outbox is installed. Sent means accepted by the mail server, not confirmed delivery.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px">
        <a class="btn" href="{{ route('quotations.index') }}">Back to Quotations</a>
        <a class="btn" href="{{ route('quotations.inbox') }}">Inbox</a>
        <a class="btn" href="{{ route('quotations.settings.edit') }}">Email Settings</a>
    </div>
    <div style="overflow:auto">
        <table style="width:100%;border-collapse:collapse;text-align:left">
            <thead><tr><th scope="col">Recipient</th><th scope="col">Subject</th><th scope="col">Attempted</th><th scope="col">Sent</th><th scope="col">Status</th></tr></thead>
            <tbody>
            @forelse($emails as $email)
                <tr>
                    <td style="padding:12px">{{ $email->recipient }}</td>
                    <td style="padding:12px"><a href="{{ route('quotations.outbox.show', $email) }}">{{ $email->subject }}</a></td>
                    <td style="padding:12px">{{ $email->created_at->timezone('Africa/Nairobi')->format('d/m/Y H:i') }} EAT</td>
                    <td style="padding:12px">{{ $email->sent_at?->timezone('Africa/Nairobi')->format('d/m/Y H:i') ?? '—' }}</td>
                    <td style="padding:12px">{{ ucfirst($email->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="padding:24px">No email attempts yet. Send a quotation to see it here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $emails->links() }}
</div>
@endsection
