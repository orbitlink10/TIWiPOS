<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
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
            'page_title' => ['nullable', 'string', 'max:255'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'heading_two' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $slugSource = $data['page_title'] ?? $data['meta_title'] ?? Str::random(6);
        $slug = Str::slug(Str::limit($slugSource, 60, ''));
        if (empty($slug)) {
            $slug = Str::random(8);
        }
        // ensure uniqueness
        $originalSlug = $slug;
        $i = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }

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

        return redirect()->route('content.create')->with('status', 'Content saved successfully.');
    }
}
