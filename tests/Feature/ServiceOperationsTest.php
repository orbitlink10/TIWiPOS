<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceVisit;
use App\Models\ServiceWorker;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_register_stylist_assign_service_and_record_completed_visit(): void
    {
        [$business, $branch] = $this->createActiveTenant('Salon Ops', 'salon-ops@example.com');

        $manager = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $workerResponse = $this->actingAs($manager)->post(route('service-workers.store'), [
            'name' => 'Grace Wanjiku',
            'title' => 'Senior Stylist',
            'phone' => '+254700000001',
            'branch_id' => $branch->id,
            'is_active' => 1,
        ]);

        $workerResponse->assertRedirect(route('services'));

        $worker = ServiceWorker::firstOrFail();

        $categoryResponse = $this->actingAs($manager)->post(route('service-categories.store'), [
            'name' => 'Hair Studio',
            'description' => 'Hair services',
            'is_active' => 1,
        ]);

        $categoryResponse->assertRedirect(route('services.create'));

        $category = ServiceCategory::firstOrFail();

        $serviceResponse = $this->actingAs($manager)->post(route('services.store'), [
            'name' => 'Silk Press',
            'service_category_id' => $category->id,
            'duration_minutes' => 90,
            'cost' => 1200,
            'price' => 3500,
            'worker_ids' => [$worker->id],
            'is_active' => 1,
        ]);

        $serviceResponse->assertRedirect(route('services'));

        $service = Service::firstOrFail();
        $this->assertDatabaseHas('service_worker_service', [
            'service_id' => $service->id,
            'service_worker_id' => $worker->id,
        ]);

        $visitResponse = $this->actingAs($manager)->post(route('service-visits.store'), [
            'customer_name' => 'Naomi Client',
            'customer_phone' => '+254711222333',
            'service_id' => $service->id,
            'service_worker_id' => $worker->id,
            'service_date' => now()->toDateString(),
            'service_time' => '10:30',
            'price' => 3500,
            'status' => ServiceVisit::STATUS_COMPLETED,
            'notes' => 'Heat protectant used',
        ]);

        $visitResponse->assertRedirect(route('services', ['date' => now()->toDateString()]));

        $visit = ServiceVisit::firstOrFail();
        $this->assertSame($business->id, $visit->business_id);
        $this->assertSame($worker->id, $visit->service_worker_id);
        $this->assertNotNull($visit->completed_at);

        $this->assertDatabaseHas('customers', [
            'name' => 'Naomi Client',
            'phone' => '+254711222333',
            'business_id' => $business->id,
        ]);

        $page = $this->actingAs($manager)->get(route('services', ['date' => now()->toDateString()]));
        $page->assertOk();
        $page->assertSee('Naomi Client');
        $page->assertSee('Grace Wanjiku');
        $page->assertSee('Silk Press');
    }

    public function test_service_visit_rejects_stylist_not_assigned_to_service(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant('Salon Guard', 'salon-guard@example.com');

        $category = ServiceCategory::create([
            'business_id' => $business->id,
            'name' => 'Spa',
            'slug' => 'spa-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);

        $assignedWorker = ServiceWorker::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'name' => 'Assigned Worker',
            'title' => 'Therapist',
            'is_active' => true,
        ]);

        $otherWorker = ServiceWorker::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'name' => 'Other Worker',
            'title' => 'Therapist',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'service_category_id' => $category->id,
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'cost' => 1000,
            'price' => 4500,
            'is_active' => true,
        ]);

        $service->workers()->attach($assignedWorker->id);

        $response = $this->actingAs($owner)
            ->from(route('services', ['date' => now()->toDateString()]))
            ->post(route('service-visits.store'), [
                'customer_name' => 'Invalid Assignment',
                'service_id' => $service->id,
                'service_worker_id' => $otherWorker->id,
                'service_date' => now()->toDateString(),
                'price' => 4500,
                'status' => ServiceVisit::STATUS_COMPLETED,
            ]);

        $response->assertRedirect(route('services', ['date' => now()->toDateString()]));
        $response->assertSessionHasErrors('service_worker_id');

        $this->assertDatabaseCount('service_visits', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_service_visit_creation_rejects_foreign_tenant_records(): void
    {
        [$businessOne, $branchOne, $ownerOne] = $this->createActiveTenant('Salon One', 'salon-one-ops@example.com');
        [$businessTwo, $branchTwo] = $this->createActiveTenant('Salon Two', 'salon-two-ops@example.com');

        $foreignCategory = ServiceCategory::create([
            'business_id' => $businessTwo->id,
            'name' => 'Foreign Spa',
            'slug' => 'foreign-spa-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);

        $foreignService = Service::create([
            'business_id' => $businessTwo->id,
            'service_category_id' => $foreignCategory->id,
            'name' => 'Foreign Facial',
            'duration_minutes' => 30,
            'cost' => 400,
            'price' => 2000,
            'is_active' => true,
        ]);

        $foreignWorker = ServiceWorker::create([
            'business_id' => $businessTwo->id,
            'branch_id' => $branchTwo->id,
            'name' => 'Foreign Worker',
            'title' => 'Stylist',
            'is_active' => true,
        ]);

        $foreignService->workers()->attach($foreignWorker->id);

        $response = $this->actingAs($ownerOne)
            ->from(route('services', ['date' => now()->toDateString()]))
            ->post(route('service-visits.store'), [
                'customer_name' => 'Tenant Leak',
                'service_id' => $foreignService->id,
                'service_worker_id' => $foreignWorker->id,
                'service_date' => now()->toDateString(),
                'price' => 2000,
                'status' => ServiceVisit::STATUS_COMPLETED,
            ]);

        $response->assertRedirect(route('services', ['date' => now()->toDateString()]));
        $response->assertSessionHasErrors(['service_id', 'service_worker_id']);

        $this->assertDatabaseMissing('service_visits', [
            'business_id' => $businessOne->id,
            'customer_name' => 'Tenant Leak',
        ]);
        $this->assertSame(0, Customer::count());
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
