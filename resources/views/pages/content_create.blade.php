@extends('layouts.app')

@section('title', 'Manage Pages')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .manage-wrap { max-width: 980px; }
    .manage-card {
        border: 1px solid #d4deec;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .manage-card-head {
        background: #1a7cea;
        color: #fff;
        font-size: 24px;
        font-weight: 800;
        padding: 14px 20px;
    }
    .manage-body {
        padding: 20px;
        display: grid;
        gap: 14px;
    }
    .field { display: grid; gap: 6px; }
    .field label {
        font-size: 16px;
        font-weight: 700;
        color: #0f2546;
    }
    .input, .select {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #cfd8e6;
        background: #fff;
        height: 52px;
        padding: 0 16px;
        font-size: 16px;
        color: #0f172a;
    }
    .file-input {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #cfd8e6;
        background: #fff;
        padding: 12px 16px;
        font-size: 14px;
        color: #0f172a;
    }
    .hint {
        color: #607086;
        font-size: 13px;
        font-weight: 600;
    }
    .status {
        margin-bottom: 12px;
        border-radius: 10px;
        padding: 10px 12px;
        font-weight: 700;
        border: 1px solid;
    }
    .status.ok {
        background: #ebf8ef;
        border-color: #bde9ca;
        color: #076c36;
    }
    .status.error {
        background: #ffe9ed;
        border-color: #f9c7d1;
        color: #9b1d30;
    }
    .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn-secondary {
        background: #f2f5fa;
        color: #102545;
        border: 1px solid #d4deec;
        text-decoration: none;
        border-radius: 11px;
        font-weight: 700;
        padding: 11px 15px;
    }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Manage Pages</h1>
        <a class="btn-secondary" href="{{ route('content.index') }}">Back to Pages</a>
    </div>
@endsection

@section('content')
    <div class="content" style="grid-template-columns: 1fr;">
        <div class="manage-wrap">
            @if(session('status'))
                <div class="status ok">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="status error">{{ $errors->first() }}</div>
            @endif

            <div class="manage-card">
                <div class="manage-card-head">Add New Post</div>
                <form class="manage-body" method="POST" action="{{ route('content.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label for="meta_title">Meta Title</label>
                        <input class="input" id="meta_title" name="meta_title" type="text" value="{{ old('meta_title') }}" placeholder="Enter Meta Title">
                    </div>
                    <div class="field">
                        <label for="meta_description">Meta Description</label>
                        <input class="input" id="meta_description" name="meta_description" type="text" value="{{ old('meta_description') }}" placeholder="Enter Meta Description">
                    </div>
                    <div class="field">
                        <label for="page_title">Page Title</label>
                        <input class="input" id="page_title" name="page_title" type="text" value="{{ old('page_title') }}" placeholder="Enter Keyword Title">
                    </div>
                    <div class="field">
                        <label for="image_alt_text">Image Alt Text</label>
                        <input class="input" id="image_alt_text" name="image_alt_text" type="text" value="{{ old('image_alt_text') }}" placeholder="Enter Image Alt Text">
                    </div>
                    <div class="field">
                        <label for="heading_two">Heading 2</label>
                        <input class="input" id="heading_two" name="heading_two" type="text" value="{{ old('heading_two') }}" placeholder="Enter Heading 2">
                    </div>
                    <div class="field">
                        <label for="type">Type</label>
                        <select class="select" id="type" name="type" required>
                            <option value="post" @selected(old('type', 'post') === 'post')>Post</option>
                            <option value="page" @selected(old('type') === 'page')>Page</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="body">Page Description</label>
                        <textarea id="body" name="body">{{ old('body') }}</textarea>
                    </div>
                    <div class="field">
                        <label for="image">Upload Image</label>
                        <input class="file-input" id="image" name="image" type="file" accept="image/*">
                        <div class="hint">Optional image for post/page preview.</div>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Save Content</button>
                        <a href="{{ route('content.index') }}" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
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
    });
</script>
@endpush
