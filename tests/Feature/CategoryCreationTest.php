<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_category_name_in_same_business_returns_validation_error(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);

        $payload = [
            'name' => 'Starlink KITS',
            'description' => 'Category for Starlink kits',
            'is_active' => 1,
        ];

        $first = $this->actingAs($user)
            ->from(route('categories.create'))
            ->post(route('categories.store'), $payload);

        $first->assertRedirect(route('products.create'));

        $second = $this->actingAs($user)
            ->from(route('categories.create'))
            ->post(route('categories.store'), $payload);

        $second->assertRedirect(route('categories.create'));
        $second->assertSessionHasErrors('name');
        $this->assertSame(1, Category::count());
    }

    public function test_same_category_name_can_exist_in_different_businesses(): void
    {
        $businessOne = $this->createActiveBusiness('Biz One', 'biz-one');
        $businessTwo = $this->createActiveBusiness('Biz Two', 'biz-two');

        $userOne = User::factory()->create([
            'business_id' => $businessOne->id,
            'role' => 'owner',
        ]);

        $userTwo = User::factory()->create([
            'business_id' => $businessTwo->id,
            'role' => 'owner',
        ]);

        $payload = [
            'name' => 'Starlink KITS',
            'description' => 'Category for Starlink kits',
            'is_active' => 1,
        ];

        $responseOne = $this->actingAs($userOne)->post(route('categories.store'), $payload);
        $responseOne->assertRedirect(route('products.create'));

        $responseTwo = $this->actingAs($userTwo)->post(route('categories.store'), $payload);
        $responseTwo->assertRedirect(route('products.create'));

        $categories = Category::withoutGlobalScopes()
            ->whereIn('business_id', [$businessOne->id, $businessTwo->id])
            ->where('name', 'Starlink KITS')
            ->orderBy('business_id')
            ->get();

        $this->assertCount(2, $categories);
        $this->assertNotSame($categories[0]->slug, $categories[1]->slug);
    }

    private function createActiveBusiness(string $name, string $slug): Business
    {
        $business = Business::create([
            'name' => $name,
            'slug' => $slug,
            'billing_email' => $slug.'@example.com',
            'status' => 'active',
            'subscription_status' => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan' => 'standard',
            'interval' => 'monthly',
            'status' => 'active',
            'amount' => 0,
            'currency' => 'KES',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
            'grace_until' => now()->addDays(3),
            'last_payment_at' => now(),
        ]);

        return $business;
    }
}
