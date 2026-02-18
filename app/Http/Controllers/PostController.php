<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Response;

class PostController extends Controller
{
    private function normalizeBodyHeadings(string $body): string
    {
        $normalized = preg_replace('/<\s*h1\b([^>]*)>/i', '<h2$1>', $body) ?? $body;
        return preg_replace('/<\s*\/\s*h1\s*>/i', '</h2>', $normalized) ?? $normalized;
    }

    private function buildUniqueSlug(
        ?string $customSlug = null,
        ?string $pageTitle = null,
        ?string $metaTitle = null,
        ?string $headingTwo = null,
        ?int $ignorePostId = null,
        ?int $fallbackPostId = null
    ): string
    {
        $slugSource = $customSlug
            ?: ($pageTitle
                ?: ($metaTitle
                    ?: ($headingTwo
                        ?: ($fallbackPostId ? ('post-' . $fallbackPostId) : 'post'))));

        $slug = Str::slug(Str::limit($slugSource, 80, ''));
        if ($slug === '') {
            $slug = $fallbackPostId ? ('post-' . $fallbackPostId) : ('post-' . Str::lower(Str::random(6)));
        }

        $originalSlug = $slug;
        $index = 1;
        while (
            Post::query()
                ->where('slug', $slug)
                ->when($ignorePostId, fn ($query) => $query->where('id', '!=', $ignorePostId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $index++;
        }

        return $slug;
    }

    private function resolveImageMeta(Post $post): array
    {
        $imageUrl = route('post.image', ['post' => $post]);
        $width = 1200;
        $height = 630;

        if ($post->image_path && Storage::disk('public')->exists($post->image_path)) {
            $path = Storage::disk('public')->path($post->image_path);
            $size = @getimagesize($path);
            if (is_array($size)) {
                $width = (int) ($size[0] ?? $width);
                $height = (int) ($size[1] ?? $height);
            }
        }

        return [
            'url' => $imageUrl,
            'width' => max(1, $width),
            'height' => max(1, $height),
        ];
    }

    private function generatePlaceholderImage(string $text = 'Post image')
    {
        $label = trim($text) !== '' ? trim($text) : 'Post image';
        $safe = e(Str::limit($label, 42, '...'));

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0%" stop-color="#e2e8f0"/>
      <stop offset="100%" stop-color="#cbd5e1"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#g)"/>
  <rect x="36" y="36" width="1128" height="558" rx="18" fill="none" stroke="#94a3b8" stroke-width="3" stroke-dasharray="10 8"/>
  <text x="600" y="314" fill="#334155" text-anchor="middle" dominant-baseline="middle" font-family="Segoe UI, Arial, sans-serif" font-size="52" font-weight="700">{$safe}</text>
</svg>
SVG;

        return Response::make($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Show form to create a post or page.
     */
    public function create()
    {
        return view('pages.content_create');
    }

    /**
     * List published posts/pages.
     */
    public function index()
    {
        $posts = Post::orderByDesc('created_at')->paginate(10);
        return view('pages.content_index', compact('posts'));
    }

    /**
     * Public blog index (crawlable).
     */
    public function blogIndex()
    {
        $posts = Post::query()
            ->where('published', true)
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('pages.blog_index', compact('posts'));
    }

    /**
     * Public image endpoint for posts (works even without storage symlink).
     */
    public function image(Post $post)
    {
        if (!empty($post->image_path) && Storage::disk('public')->exists($post->image_path)) {
            return Storage::disk('public')->response($post->image_path, null, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return $this->generatePlaceholderImage($post->page_title ?? $post->meta_title ?? 'Post image');
    }

    /**
     * Show a single published post/page by slug.
     */
    public function show(Post $post)
    {
        $canonicalSlug = $this->buildUniqueSlug(
            null,
            $post->page_title,
            $post->meta_title,
            $post->heading_two,
            $post->id,
            $post->id
        );

        if ($canonicalSlug !== $post->slug) {
            $post->update(['slug' => $canonicalSlug]);
            return redirect()->route('post.show', ['post' => $canonicalSlug], 301);
        }

        $renderedBody = $this->normalizeBodyHeadings((string) ($post->body ?? ''));
        $readMinutes = max(1, (int) ceil(str_word_count(strip_tags($renderedBody)) / 200));
        $imageMeta = $this->resolveImageMeta($post);
        $seoTitle = $post->meta_title ?: ($post->page_title ?: 'Tiwi Blog');
        $seoDescriptionSource = $post->meta_description ?: strip_tags($renderedBody);
        $seoDescription = Str::of((string) $seoDescriptionSource)->squish()->limit(160)->toString();
        $canonicalUrl = route('post.show', ['post' => $post->slug]);

        return view('pages.content_show', compact(
            'post',
            'readMinutes',
            'renderedBody',
            'imageMeta',
            'seoTitle',
            'seoDescription',
            'canonicalUrl'
        ));
    }

    /**
     * Store a post or page.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:post,page'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9-]+$/'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'page_title' => ['required', 'string', 'max:255'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'heading_two' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $slug = $this->buildUniqueSlug(
            $data['slug'] ?? null,
            $data['page_title'] ?? null,
            $data['meta_title'] ?? null,
            $data['heading_two'] ?? null
        );

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }
        $normalizedBody = $this->normalizeBodyHeadings($data['body']);

        Post::create([
            'type' => $data['type'],
            'slug' => $slug,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'page_title' => $data['page_title'] ?? null,
            'image_alt_text' => $data['image_alt_text'] ?? null,
            'heading_two' => $data['heading_two'] ?? null,
            'body' => $normalizedBody,
            'image_path' => $imagePath,
            'published' => true,
        ]);

        return redirect()->route('content.index')->with('status', 'Content saved successfully.');
    }

    /**
     * Show form to edit existing post/page.
     */
    public function edit(Post $post)
    {
        return view('pages.content_edit', compact('post'));
    }

    /**
     * Update existing post/page.
     */
    public function update(Request $request, Post $post)
    {
        $data = $request->validate([
            'type' => ['required', 'in:post,page'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9-]+$/'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'page_title' => ['required', 'string', 'max:255'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'heading_two' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $imagePath = $post->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $slug = $this->buildUniqueSlug(
            $data['slug'] ?? null,
            $data['page_title'] ?? null,
            $data['meta_title'] ?? null,
            $data['heading_two'] ?? null,
            $post->id,
            $post->id
        );
        $normalizedBody = $this->normalizeBodyHeadings($data['body']);

        $post->update([
            'type' => $data['type'],
            'slug' => $slug,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'page_title' => $data['page_title'] ?? null,
            'image_alt_text' => $data['image_alt_text'] ?? null,
            'heading_two' => $data['heading_two'] ?? null,
            'body' => $normalizedBody,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('content.index')->with('status', 'Content updated successfully.');
    }

    /**
     * Delete a post/page.
     */
    public function destroy(Post $post)
    {
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return redirect()->route('content.index')->with('status', 'Content deleted successfully.');
    }

    /**
     * Apply bulk actions on posts/pages.
     */
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete'])],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'integer', 'exists:posts,id'],
        ]);

        $posts = Post::query()->whereIn('id', $data['selected'])->get();
        foreach ($posts as $post) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $post->delete();
        }

        return redirect()->route('content.index')->with('status', 'Selected content deleted successfully.');
    }

    /**
     * XML sitemap for public pages/posts.
     */
    public function sitemap()
    {
        $posts = Post::query()
            ->where('published', true)
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at', 'created_at']);

        return response()
            ->view('sitemap', compact('posts'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
