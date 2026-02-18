<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    private function buildUniqueSlug(?string $pageTitle, ?string $metaTitle, ?int $ignorePostId = null): string
    {
        $slugSource = $pageTitle ?: ($metaTitle ?: Str::random(6));
        $slug = Str::slug(Str::limit($slugSource, 60, ''));
        if ($slug === '') {
            $slug = Str::random(8);
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
     * Show a single published post/page by slug.
     */
    public function show(Post $post)
    {
        if (($post->page_title || $post->meta_title)) {
            $canonicalSlug = $this->buildUniqueSlug(
                $post->page_title,
                $post->meta_title,
                $post->id
            );

            if ($canonicalSlug !== $post->slug) {
                $post->update(['slug' => $canonicalSlug]);
                return redirect()->route('post.show', ['post' => $canonicalSlug], 301);
            }
        }

        $readMinutes = max(1, (int) ceil(str_word_count(strip_tags($post->body ?? '')) / 200));
        return view('pages.content_show', compact('post', 'readMinutes'));
    }

    /**
     * Store a post or page.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:post,page'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'page_title' => ['required', 'string', 'max:255'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'heading_two' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $slug = $this->buildUniqueSlug($data['page_title'] ?? null, $data['meta_title'] ?? null);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        Post::create([
            'type' => $data['type'],
            'slug' => $slug,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'page_title' => $data['page_title'] ?? null,
            'image_alt_text' => $data['image_alt_text'] ?? null,
            'heading_two' => $data['heading_two'] ?? null,
            'body' => $data['body'],
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
            $data['page_title'] ?? null,
            $data['meta_title'] ?? null,
            $post->id
        );

        $post->update([
            'type' => $data['type'],
            'slug' => $slug,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'page_title' => $data['page_title'] ?? null,
            'image_alt_text' => $data['image_alt_text'] ?? null,
            'heading_two' => $data['heading_two'] ?? null,
            'body' => $data['body'],
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
}
