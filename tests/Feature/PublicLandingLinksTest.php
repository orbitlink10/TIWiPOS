<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_has_marketing_section_anchors(): void
    {
        $response = $this->get(route('site.home'));

        $response->assertOk();
        $response->assertSee('id="products"', false);
        $response->assertSee('id="pricing"', false);
        $response->assertSee('id="resources"', false);
        $response->assertSee('id="company"', false);
        $response->assertSee('id="app-center"', false);
        $response->assertSee('id="enterprise"', false);
    }

    public function test_blog_and_post_pages_link_to_public_site_sections(): void
    {
        $post = Post::create([
            'type' => 'post',
            'slug' => 'test-public-linking',
            'meta_title' => 'Test Public Linking',
            'meta_description' => 'Public navigation links should point to the site landing page.',
            'page_title' => 'Test Public Linking',
            'body' => '<p>Body content.</p>',
            'published' => true,
        ]);

        $blogResponse = $this->get(route('blog.index'));
        $blogResponse->assertOk();
        $blogResponse->assertSee(route('site.home') . '#products');
        $blogResponse->assertSee(route('site.home'));

        $postResponse = $this->get(route('post.show', ['post' => $post->slug]));
        $postResponse->assertOk();
        $postResponse->assertSee(route('site.home') . '#enterprise');
        $postResponse->assertSee(route('site.home'));
    }
}
