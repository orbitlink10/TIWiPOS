@extends('layouts.app')

@section('title', 'Pages')

@push('styles')
<style>
    .pages-wrap { display: grid; gap: 14px; }
    .theme-hero {
        background: linear-gradient(140deg, #b41543 0%, #8e1238 100%);
        color: #fff;
        border-radius: 14px;
        padding: 20px 22px;
        box-shadow: 0 16px 34px rgba(133, 18, 55, 0.28);
    }
    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.9);
    }
    .pages-title { margin: 8px 0 0; font-size: 28px; font-weight: 800; letter-spacing: -0.02em; color: #fff; }
    .pages-subtitle { margin: 4px 0 0; color: rgba(255,255,255,0.88); font-size: 14px; max-width: 780px; }
    .pages-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }
    .pages-card-head {
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
        gap: 10px;
    }
    .pages-card-head h2 { margin: 0; font-size: 21px; color: #0f172a; }
    .add-page-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        background: linear-gradient(135deg, #ff7d34 0%, #ff5e2a 100%);
        color: #fff;
        text-decoration: none;
        font-weight: 800;
        padding: 10px 16px;
        font-size: 15px;
        border: 1px solid transparent;
    }
    .pages-toolbar {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .bulk-select {
        width: 155px;
        border-radius: 10px;
        border: 1px solid #c9d5e9;
        background: #fff;
        height: 42px;
        padding: 0 12px;
    }
    .apply-btn {
        border: none;
        border-radius: 999px;
        padding: 10px 20px;
        font-weight: 800;
        cursor: pointer;
        background: #1f2937;
        color: #fff;
    }
    .pages-table-wrap {
        padding: 0 20px 16px;
        overflow: auto;
    }
    .pages-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 980px;
    }
    .pages-table th {
        padding: 12px 10px;
        text-align: left;
        font-size: 12px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #6b7280;
        border-bottom: 1px solid var(--border);
        background: #f8fafc;
    }
    .pages-table td {
        padding: 18px 10px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .page-number { width: 52px; font-weight: 700; color: #1f355a; }
    .thumb {
        width: 180px;
        height: 120px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #d7e1f2;
        background: #eef2f9;
    }
    .title-cell {
        font-size: 15px;
        font-weight: 700;
        color: #102b4d;
        line-height: 1.35;
    }
    .alt-cell { color: #2f4668; font-size: 14px; }
    .type-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #1f2937;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        text-transform: capitalize;
    }
    .actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    .action-btn {
        text-decoration: none;
        font-weight: 700;
        border-radius: 999px;
        padding: 7px 14px;
        border: 1px solid;
        background: #fff;
        font-size: 14px;
        line-height: 1;
    }
    .action-preview { color: #0f9dbf; border-color: #0fb0d4; }
    .action-update { color: #e69b00; border-color: #f0b02f; }
    .action-delete { color: #db3348; border-color: #ee5366; }
    .status-msg {
        padding: 10px 12px;
        border-radius: 10px;
        background: #ebf8ef;
        border: 1px solid #bde9ca;
        color: #076c36;
        font-weight: 700;
    }
    .error-msg {
        padding: 10px 12px;
        border-radius: 10px;
        background: #ffe9ed;
        border: 1px solid #f9c7d1;
        color: #9b1d30;
        font-weight: 700;
    }
    .empty-row {
        color: #6a7e95;
        font-weight: 600;
        text-align: center;
    }
    .thumb-fallback {
        width: 180px;
        height: 120px;
        border-radius: 6px;
        border: 1px solid #d7e1f2;
        background: #eef2f9;
        color: #67809f;
        font-weight: 700;
        display: grid;
        place-items: center;
        font-size: 13px;
    }
    @media (max-width: 980px) {
        .pages-title { font-size: 24px; }
        .pages-subtitle { font-size: 14px; }
    }
</style>
@endpush

@section('header')
    <div class="pages-wrap">
        <div class="theme-hero">
            <div class="hero-kicker">Tiwi Blog CMS</div>
            <h1 class="pages-title">Pages</h1>
            <p class="pages-subtitle">Manage site pages and published content.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="content" style="grid-template-columns: 1fr;">
        @if (session('status'))
            <div class="status-msg">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="error-msg">{{ $errors->first() }}</div>
        @endif

        <div class="pages-card">
            <div class="pages-card-head">
                <h2>Post List</h2>
                <a href="{{ route('content.create') }}" class="add-page-btn">+ Add Page</a>
            </div>

            <form method="POST" action="{{ route('content.bulk') }}">
                @csrf
                <div class="pages-toolbar">
                    <select name="action" class="bulk-select" required>
                        <option value="" disabled selected>Bulk actions</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="apply-btn">Apply</button>
                </div>

                <div class="pages-table-wrap">
                    <table class="pages-table">
                        <thead>
                            <tr>
                                <th style="width:44px;">
                                    <input type="checkbox" id="select_all">
                                </th>
                                <th style="width:80px;">No.</th>
                                <th style="width:220px;">Image</th>
                                <th>Title</th>
                                <th>Alt Text</th>
                                <th style="width:120px;">Type</th>
                                <th style="width:170px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($posts as $post)
                                @php
                                    $derivedSlugTitle = \Illuminate\Support\Str::of((string) $post->slug)->replace('-', ' ')->title();
                                    $displayTitle = $post->page_title ?: ($post->meta_title ?: ($post->heading_two ?: ($derivedSlugTitle ?: 'Untitled')));
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="row-check" name="selected[]" value="{{ $post->id }}">
                                    </td>
                                    <td class="page-number">{{ ($posts->firstItem() ?? 1) + $loop->index }}</td>
                                    <td>
                                        @if($post->image_path)
                                            <div style="position:relative; width:180px;">
                                                <img class="thumb" src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->image_alt_text ?: $displayTitle }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                                <div class="thumb-fallback" style="display:none;">No image</div>
                                            </div>
                                        @else
                                            <div class="thumb-fallback">No image</div>
                                        @endif
                                    </td>
                                    <td class="title-cell">{{ $displayTitle }}</td>
                                    <td class="alt-cell">{{ $post->image_alt_text ?: '-' }}</td>
                                    <td><span class="type-pill">{{ $post->type }}</span></td>
                                    <td>
                                        <div class="actions">
                                            <a href="{{ route('post.show', ['post' => $post->slug]) }}" class="action-btn action-preview">Preview</a>
                                            <a href="{{ route('content.edit', $post) }}" class="action-btn action-update">Update</a>
                                            <form method="POST" action="{{ route('content.destroy', $post) }}" onsubmit="return confirm('Delete this page/post?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn action-delete" style="cursor:pointer;">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty-row">No pages/posts yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            <div style="padding: 0 20px 16px;">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var selectAll = document.getElementById('select_all');
        if (!selectAll) return;
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (check) {
                check.checked = selectAll.checked;
            });
        });
    })();
</script>
@endpush
