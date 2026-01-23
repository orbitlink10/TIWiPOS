@extends('layouts.app')

@section('title', $post->page_title ?? $post->meta_title ?? 'Preview')

@push('styles')
<style>
    .hero-article {
        background: #0b7c73;
        color: #fff;
        padding: 38px 42px;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    }
    .hero-article h1 { margin: 0 0 12px; font-size: 38px; line-height: 1.05; }
    .breadcrumbs { display:flex; gap:8px; align-items:center; font-weight:700; color: rgba(255,255,255,0.8); margin-bottom: 14px; }
    .breadcrumbs span { opacity: 0.9; }
    .meta-line { color: rgba(255,255,255,0.9); font-weight:600; }
    .article-body {
        background: #fff;
        margin-top: 16px;
        padding: 22px;
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 16px 40px rgba(0,0,0,0.04);
    }
    .article-body img { max-width: 100%; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 14px; }
    .article-body h2, .article-body h3 { color: #0f172a; margin-top: 18px; }
    .article-body p { line-height: 1.7; color: #1f2937; }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1 style="margin:0;">Preview</h1>
        <a class="btn" href="{{ route('content.index') }}">Back to list</a>
    </div>
@endsection

@section('content')
    <div class="content" style="grid-template-columns: 1fr;">
        <div class="hero-article">
            <div class="breadcrumbs">
                <span>Tiwi POS</span><span>›</span><span>{{ ucfirst($post->type) }}</span><span>›</span><span>{{ $post->page_title ?? 'Preview' }}</span>
            </div>
            <h1>{{ $post->page_title ?? $post->meta_title ?? 'Preview' }}</h1>
            <div class="meta-line">
                {{ $post->created_at->format('M d, Y') }} · {{ $readMinutes }} min read
            </div>
        </div>

        <div class="article-body">
            @if($post->image_path)
                <img src="{{ asset('storage/'.$post->image_path) }}" alt="{{ $post->image_alt_text }}">
            @endif
            @if($post->heading_two)
                <h2>{{ $post->heading_two }}</h2>
            @endif
            @if($post->meta_description)
                <p style="font-weight:600; color:#374151;">{{ $post->meta_description }}</p>
            @endif
            <div>{!! $post->body !!}</div>
        </div>
    </div>
@endsection
