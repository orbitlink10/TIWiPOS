<?php

namespace Tests\Feature;

use App\Http\Middleware\FeatureGate;
use App\Models\Business;
use App\Models\TodoItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoListTest extends TestCase
{
    use RefreshDatabase;

    private function journalUser(): User
    {
        $business = Business::create([
            'name' => 'Journal Business', 'slug' => 'journal-'.uniqid(),
            'status' => 'active', 'subscription_status' => 'active',
        ]);

        return User::factory()->create(['business_id' => $business->id, 'role' => 'owner']);
    }

    public function test_activity_can_be_created_completed_and_reopened(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $user = $this->journalUser();
        $this->actingAs($user)->post(route('todos.store'), [
            'activity' => 'Check daily stock', 'activity_date' => '2026-10-19',
            'business_id' => 999, 'user_id' => 999, 'is_completed' => true,
        ])->assertRedirect(route('todos.index', ['date' => '2026-10-19']));

        $item = TodoItem::sole();
        $this->assertSame($user->business_id, $item->business_id);
        $this->assertSame($user->id, $item->user_id);
        $this->assertFalse($item->is_completed);
        $this->actingAs($user)->patch(route('todos.update', $item), ['is_completed' => 1])->assertRedirect();
        $this->assertTrue($item->fresh()->is_completed);
        $this->get(route('todos.index', ['date' => '2026-10-19']))->assertOk()
            ->assertSee('To Do List')->assertSee('Check daily stock')
            ->assertSee('todo-item todo-done', false)->assertSee('✓');
        $this->patch(route('todos.update', $item), ['is_completed' => 0])->assertRedirect();
        $this->assertFalse($item->fresh()->is_completed);
    }

    public function test_journal_is_private_to_user_and_business_and_filtered_by_date(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $owner = $this->journalUser();
        $colleague = User::factory()->create(['business_id' => $owner->business_id]);
        $outsider = $this->journalUser();
        $item = TodoItem::create([
            'business_id' => $owner->business_id, 'user_id' => $owner->id,
            'activity' => 'Private journal entry', 'activity_date' => '2026-10-19',
        ]);
        foreach ([$colleague, $outsider] as $user) {
            $this->actingAs($user)->get(route('todos.index', ['date' => '2026-10-19']))
                ->assertOk()->assertDontSee('Private journal entry');
            $this->patch(route('todos.update', $item), ['is_completed' => 1])->assertNotFound();
        }
        $this->actingAs($owner)->get(route('todos.index', ['date' => '2026-10-20']))
            ->assertOk()->assertDontSee('Private journal entry');
        $this->assertFalse($item->fresh()->is_completed);
    }

    public function test_date_and_activity_are_required_and_guests_cannot_access_journal(): void
    {
        $this->get(route('todos.index'))->assertRedirect(route('login'));
        $this->withoutMiddleware(FeatureGate::class);
        $this->actingAs($this->journalUser())->post(route('todos.store'), [])
            ->assertSessionHasErrors(['activity', 'activity_date']);
        $this->get(route('todos.index', ['date' => 'invalid']))->assertSessionHasErrors('date');
        $this->post(route('todos.store'), ['activity' => 'Test', 'activity_date' => '2026-02-30'])
            ->assertSessionHasErrors('activity_date');
        $this->assertDatabaseCount('todo_items', 0);
    }
}
