<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_service_category_name_in_same_business_returns_validation_error(): void
    {
        [, , $owner] = $this->createActiveTenant('Service Biz One', 'service-one@example.com');

        $payload = [
            'name' => 'Hair Studio',
            'description' => 'Hair services',
            'is_active' => 1,
        ];

        $first = $this->actingAs($owner)
            ->from(route('service-categories.create'))
            ->post(route('service-categories.store'), $payload);

        $first->assertRedirect(route('services.create'));

        $second = $this->actingAs($owner)
            ->from(route('service-categories.create'))
            ->post(route('service-categories.store'), $payload);

        $second->assertRedirect(route('service-categories.create'));
        $second->assertSessionHasErrors('name');
        $this->assertSame(1, ServiceCategory::count());
    }

    public function test_same_service_category_name_can_exist_in_different_businesses(): void
    {
        [$businessOne, , $ownerOne] = $this->createActiveTenant('Salon One', 'salon-one@example.com');
        [$businessTwo, , $ownerTwo] = $this->createActiveTenant('Salon Two', 'salon-two@example.com');

        $payload = [
            'name' => 'Nail Bar',
            'description' => 'Nail services',
            'is_active' => 1,
        ];

        $responseOne = $this->actingAs($ownerOne)->post(route('service-categories.store'), $payload);
        $responseOne->assertRedirect(route('services.create'));

        $responseTwo = $this->actingAs($ownerTwo)->post(route('service-categories.store'), $payload);
        $responseTwo->assertRedirect(route('services.create'));

        $categories = ServiceCategory::withoutGlobalScopes()
            ->where('name', 'Nail Bar')
            ->whereIn('business_id', [$businessOne->id, $businessTwo->id])
            ->orderBy('business_id')
            ->get();

        $this->assertCount(2, $categories);
        $this->assertNotSame($categories[0]->slug, $categories[1]->slug);
    }

    public function test_service_creation_rejects_category_from_another_business(): void
    {
        [$firstBusiness, , $firstOwner] = $this->createActiveTenant('Catalog Services One', 'catalog-services-one@example.com');
        [$secondBusiness] = $this->createActiveTenant('Catalog Services Two', 'catalog-services-two@example.com');

        $foreignCategory = ServiceCategory::create([
            'business_id' => $secondBusiness->id,
            'name' => 'Foreign Category',
            'slug' => 'foreign-category-service',
            'is_active' => true,
        ]);

        $response = $this->actingAs($firstOwner)
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Deep Tissue Massage',
                'service_category_id' => $foreignCategory->id,
                'duration_minutes' => 60,
                'cost' => 1000,
                'price' => 3500,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('services.create'));
        $response->assertSessionHasErrors('service_category_id');

        $this->assertDatabaseMissing('services', [
            'name' => 'Deep Tissue Massage',
            'business_id' => $firstBusiness->id,
        ]);
    }

    public function test_service_can_be_created_and_listed(): void
    {
        [$business, , $owner] = $this->createActiveTenant('Service Retail', 'service-retail@example.com');

        $category = ServiceCategory::create([
            'business_id' => $business->id,
            'name' => 'Skin Care',
            'slug' => 'skin-care-'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Express Facial',
                'service_category_id' => $category->id,
                'duration_minutes' => 30,
                'cost' => 900,
                'price' => 2200,
                'description' => 'Quick glow treatment',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('services'));

        $service = Service::withoutGlobalScopes()->first();
        $this->assertNotNull($service);
        $this->assertSame($business->id, $service->business_id);

        $page = $this->actingAs($owner)->get(route('services'));
        $page->assertOk();
        $page->assertSee('Express Facial');
        $page->assertSee('Skin Care');
        $page->assertSee('30 min');
    }

    private function createActiveTenant(string $name, string $billingEmail): array
    {
        $business = Business::create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . Str::lower(Str::random(6)),
            'billing_email' => $billingEmail,
            'status' => 'active',
            'subscription_status' => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_default' => true,
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

        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
            'is_active' => true,
        ]);

        return [$business, $branch, $owner];
    }
}
