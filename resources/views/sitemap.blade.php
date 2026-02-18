<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
    </url>
    <url>
        <loc>{{ route('blog.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
    </url>
    @foreach($posts as $post)
        <url>
            <loc>{{ route('post.show', ['post' => $post->slug]) }}</loc>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
            <lastmod>{{ ($post->updated_at ?? $post->created_at ?? now())->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
