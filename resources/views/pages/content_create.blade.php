@extends('layouts.app')

@section('title', 'Create Content')

@push('styles')
{{-- TinyMCE via jsDelivr (no API key required) --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .content-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 12px 28px rgba(0,0,0,0.04);
    }
    .content-card h2 { margin: 0 0 12px; }
    .form-grid { display: grid; gap: 14px; }
    .form-grid label { font-weight: 700; color: #0f172a; display:block; margin-bottom:6px; }
    .form-grid input[type="text"],
    .form-grid input[type="file"],
    .form-grid select {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: #f9fbfd;
        font-size: 15px;
    }
    .hint { color: var(--muted); font-size: 13px; margin-top: 4px; }
    .section-title { font-size: 18px; font-weight: 800; margin: 10px 0 6px; }
    .status {
        padding: 10px 12px;
        border-radius: 10px;
        background: #e8f5ff;
        border: 1px solid var(--border);
        color: #0f172a;
        font-weight: 600;
        margin-bottom: 10px;
    }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Create Page / Blog Post</h1>
        <div>
            @if(session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="status" style="background:#ffe4e6;color:#7f1d1d;border-color:#fecdd3;">
                    {{ $errors->first() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="content">
        <div class="panel" style="grid-column: 1 / -1;">
            <div class="section-title">Add New Post</div>
            <form class="form-grid" method="POST" action="{{ route('content.store') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="post">Post</option>
                        <option value="page">Page</option>
                    </select>
                </div>
                <div>
                    <label for="meta_title">Meta Title</label>
                    <input type="text" id="meta_title" name="meta_title" placeholder="Enter Meta Title" value="{{ old('meta_title') }}">
                </div>
                <div>
                    <label for="meta_description">Meta Description</label>
                    <input type="text" id="meta_description" name="meta_description" placeholder="Enter Meta Description" value="{{ old('meta_description') }}">
                </div>
                <div>
                    <label for="page_title">Page Title</label>
                    <input type="text" id="page_title" name="page_title" placeholder="Enter Keyword Title" value="{{ old('page_title') }}">
                </div>
                <div>
                    <label for="image_alt_text">Image Alt Text</label>
                    <input type="text" id="image_alt_text" name="image_alt_text" placeholder="Enter Image Alt Text" value="{{ old('image_alt_text') }}">
                </div>
                <div>
                    <label for="heading_two">Heading 2</label>
                    <input type="text" id="heading_two" name="heading_two" placeholder="Enter Heading 2" value="{{ old('heading_two') }}">
                </div>

                <div>
                    <label for="body">Page Description</label>
                    <textarea id="body" name="body">{{ old('body') }}</textarea>
                </div>

                <div>
                    <label for="image">Upload Image (optional, only for Posts)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div class="hint">Recommended for blog posts; ignored for pages.</div>
                </div>

                <div>
                    <button class="btn" type="submit">Publish</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    tinymce.init({
        selector: '#body',
        plugins: 'link image media table code lists',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image media | table | code',
        menubar: 'file edit view insert format tools table',
        height: 420,
        branding: false,
        skin: 'oxide',
        content_css: 'default'
    });
</script>
@endpush
