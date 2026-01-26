@extends('layouts.app')

@section('title', 'Published Content')

@section('header')
    <div class="header-row">
        <h1>Published Pages & Posts</h1>
    </div>
@endsection

@section('content')
    <div class="panel" style="grid-column: 1 / -1;">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid var(--border);">
                    <th style="padding:10px;">Type</th>
                    <th style="padding:10px;">Title</th>
                    <th style="padding:10px;">Slug</th>
                    <th style="padding:10px;">Preview</th>
                    <th style="padding:10px;">Created</th>
                </tr>
            </thead>
            <tbody>
            @forelse($posts as $post)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:10px; text-transform:capitalize;">{{ $post->type }}</td>
                    <td style="padding:10px;">{{ $post->page_title ?? $post->meta_title ?? '—' }}</td>
                    <td style="padding:10px;">{{ $post->slug }}</td>
                    <td style="padding:10px;"><a class="btn ghost" href="{{ route('content.show', $post) }}">View</a></td>
                    <td style="padding:10px;">{{ $post->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="padding:12px; color:var(--muted);">No content yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px;">
            {{ $posts->links() }}
        </div>
    </div>
@endsection
