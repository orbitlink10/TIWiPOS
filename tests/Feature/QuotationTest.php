<?php

namespace Tests\Feature;

use App\Http\Middleware\FeatureGate;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $business = Business::create(['name' => 'Test Shop', 'slug' => uniqid('shop-'), 'status' => 'active']);

        return User::factory()->create(['business_id' => $business->id, 'role' => 'owner']);
    }

    private function payload(): array
    {
        return [
            'number' => 'QT-100', 'customer_name' => 'Customer Limited',
            'quotation_date' => '2026-10-29', 'valid_until' => '2026-11-12',
            'issuer_name' => 'Test Shop', 'currency' => 'USD', 'discount' => '10.00', 'vat_rate' => '16',
            'items' => [
                ['description' => 'Standard Kit', 'quantity' => '2', 'unit' => 'PCS', 'unit_price' => '395.00'],
                ['description' => 'Installation', 'quantity' => '1.5', 'unit' => 'HR', 'unit_price' => '20.00'],
            ],
        ];
    }

    public function test_create_edit_and_print_with_server_calculated_totals(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $user = $this->owner();
        $this->actingAs($user)->get(route('quotations.create'))->assertOk()->assertSee('Prepare Quotation');
        $this->post(route('quotations.store'), $this->payload() + ['business_id' => 999, 'total_cents' => 1])
            ->assertSessionHasNoErrors()->assertRedirect();
        $quote = Quotation::sole();
        $this->assertEquals($user->business_id, $quote->business_id);
        $this->assertSame(82000, $quote->document['subtotal_cents']);
        $this->assertSame(12960, $quote->document['vat_cents']);
        $this->assertSame(93960, $quote->document['total_cents']);
        $this->get(route('quotations.show', $quote))->assertOk()->assertSee('USD 939.60')->assertSee('CUSTOMER APPROVAL');
        $this->get(route('quotations.edit', $quote))->assertOk()->assertSee('Standard Kit');
        $data = $this->payload();
        $data['vat_rate'] = 0;
        $data['customer_name'] = '<script>alert(1)</script>';
        $this->put(route('quotations.update', $quote), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('quotations.show', $quote))->assertOk()->assertSee('USD 810.00')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('quotations.index'))->assertOk()->assertSeeInOrder(['To Do List', 'Quotation']);
    }

    public function test_quotations_are_isolated_by_business_and_branch(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $owner = $this->owner();
        $branch = Branch::create(['business_id' => $owner->business_id, 'name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
        $this->actingAs($owner)->post(route('quotations.store'), $this->payload())->assertRedirect();
        $quote = Quotation::sole();
        $this->assertEquals($branch->id, $quote->branch_id);
        $otherBranch = Branch::create(['business_id' => $owner->business_id, 'name' => 'Other', 'code' => 'OTHER']);
        $this->withSession(['branch_id' => $otherBranch->id])->get(route('quotations.show', $quote))->assertNotFound();
        $this->get(route('quotations.edit', $quote))->assertNotFound();
        $this->put(route('quotations.update', $quote), $this->payload())->assertNotFound();
        $this->get(route('quotations.index'))->assertOk()->assertDontSee('Customer Limited');
        $this->actingAs($this->owner())->get(route('quotations.show', $quote))->assertNotFound();
        $this->put(route('quotations.update', $quote), $this->payload())->assertNotFound();
    }

    public function test_invalid_amounts_dates_and_duplicate_numbers_are_rejected(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $this->actingAs($this->owner());
        $data = $this->payload();
        $data['discount'] = 9999;
        $this->post(route('quotations.store'), $data)->assertSessionHasErrors('discount');
        $data = $this->payload();
        $data['items'][0]['quantity'] = 1000000;
        $data['items'][0]['unit_price'] = 999999999;
        $this->post(route('quotations.store'), $data)->assertSessionHasErrors('items');
        $data = $this->payload();
        $data['valid_until'] = '2026-10-01';
        $data['items'][0]['quantity'] = -1;
        $this->post(route('quotations.store'), $data)->assertSessionHasErrors(['valid_until', 'items.0.quantity']);
        $this->assertDatabaseCount('quotations', 0);
        $this->post(route('quotations.store'), $this->payload())->assertRedirect();
        $this->post(route('quotations.store'), $this->payload())->assertSessionHasErrors('number');
    }

    public function test_business_defaults_and_logo_are_saved_and_snapshotted(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        \Illuminate\Support\Facades\Storage::fake('local');
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('quotations.index'))->assertSee('Settings');
        $settings = ['issuer_name' => 'Default Company', 'currency' => 'KES', 'vat_rate' => 16,
            'terms' => 'Payment within 30 days.', 'bank_details' => 'Bank account 12345'];
        $this->put(route('quotations.settings.update'), $settings + [
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 120, 80),
        ])->assertSessionHasNoErrors()->assertRedirect(route('quotations.settings.edit'));
        $defaults = Business::findOrFail($owner->business_id)->quotation_defaults;
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($defaults['logo_path']);
        $this->get(route('quotations.settings.edit'))->assertOk()->assertSee('Current business logo');
        $this->get(route('quotations.create'))->assertOk()->assertSee('Default Company')->assertSee('Payment within 30 days.')->assertSee('Bank account 12345');
        $this->post(route('quotations.store'), array_replace($this->payload(), $settings))->assertRedirect();
        $quote = Quotation::sole();
        $this->assertSame($defaults['logo_path'], $quote->document['logo_path']);
        $this->get(route('quotations.show', $quote))->assertOk()->assertSee('data:image/png;base64,', false);
        $this->get(route('quotations.download', $quote))->assertOk()->assertDownload();
        $this->put(route('quotations.settings.update'), array_replace($settings, ['terms' => 'New terms', 'remove_logo' => 1]))->assertSessionHasNoErrors();
        $this->put(route('quotations.update', $quote), array_replace($this->payload(), $settings))->assertRedirect();
        $this->assertSame($defaults['logo_path'], $quote->fresh()->document['logo_path']);
        $this->assertSame('Payment within 30 days.', $quote->fresh()->document['terms']);
        $this->actingAs($this->owner())->get(route('quotations.create'))->assertDontSee('Default Company')->assertDontSee('Bank account 12345');
        $staff = User::factory()->create(['business_id' => $owner->business_id, 'role' => 'staff']);
        $this->actingAs($staff)->get(route('quotations.settings.edit'))->assertForbidden();
        $this->put(route('quotations.settings.update'), $settings)->assertForbidden();
    }

    public function test_quotation_settings_reject_invalid_logo_and_defaults(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $this->actingAs($this->owner())->put(route('quotations.settings.update'), [
            'issuer_name' => '', 'currency' => 'INVALID', 'vat_rate' => 101,
            'logo' => \Illuminate\Http\UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors(['issuer_name', 'currency', 'vat_rate', 'logo']);
    }

    public function test_mailbox_settings_are_encrypted_and_used_for_quotation_sending(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $owner = $this->owner();
        $settings = ['from_address' => 'sales@example.com', 'from_name' => 'Sales Team',
            'host' => 'mail.example.com', 'port' => 465, 'username' => 'sales@example.com', 'mail_password' => 'secret-app-password'];
        $this->actingAs($owner)->put(route('quotations.settings.email'), $settings)->assertSessionHasNoErrors();
        $business = Business::findOrFail($owner->business_id);
        $this->assertSame('secret-app-password', $business->quotation_mail['password']);
        $this->assertStringNotContainsString('secret-app-password', $business->getRawOriginal('quotation_mail'));
        $this->get(route('quotations.settings.edit'))->assertOk()->assertDontSee('secret-app-password');
        $settings['mail_password'] = '';
        $this->put(route('quotations.settings.email'), $settings)->assertSessionHasNoErrors();
        $this->assertSame('secret-app-password', $business->fresh()->quotation_mail['password']);
        $this->post(route('quotations.store'), $this->payload())->assertRedirect();
        $pending = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $pending->shouldReceive('send')->once()->withArgs(function ($mail) {
            return $mail->from[0]['address'] === 'sales@example.com'
                && $mail->replyTo[0]['address'] === 'sales@example.com'
                && str_starts_with($mail->pdf, '%PDF-');
        });
        $mailer = \Mockery::mock(\Illuminate\Mail\Mailer::class);
        $mailer->shouldReceive('to')->once()->with('customer@example.com')->andReturn($pending);
        \Illuminate\Support\Facades\Mail::shouldReceive('build')->once()->withArgs(fn ($config) =>
            $config['host'] === 'mail.example.com' && $config['scheme'] === 'smtps'
            && $config['password'] === 'secret-app-password' && $config['require_tls'] === true
        )->andReturn($mailer);
        $this->post(route('quotations.email', Quotation::sole()), ['email' => 'customer@example.com'])->assertSessionHasNoErrors();
        $this->actingAs($this->owner())->get(route('quotations.settings.edit'))->assertDontSee('sales@example.com');
    }

    public function test_mailbox_password_is_not_flashed_on_validation_failure(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $this->actingAs($this->owner())->put(route('quotations.settings.email'), [
            'from_address' => 'invalid', 'mail_password' => 'never-flash-this',
        ])->assertSessionHasErrors('from_address')->assertSessionMissing('_old_input.mail_password');
    }

    public function test_guests_cannot_access_quotations(): void
    {
        $this->get(route('quotations.index'))->assertRedirect(route('login'));
        $this->post(route('quotations.store'), $this->payload())->assertRedirect(route('login'));
        $this->get(route('quotations.download', 1))->assertRedirect(route('login'));
        $this->post(route('quotations.email', 1), ['email' => 'customer@example.com'])->assertRedirect(route('login'));
    }

    public function test_download_and_email_pdf_with_tenant_isolation(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->owner())->post(route('quotations.store'), $this->payload())->assertRedirect();
        $quote = Quotation::sole();
        $response = $this->get(route('quotations.download', $quote))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('Quotation-qt-100.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->post(route('quotations.email', $quote), ['email' => 'invalid'])->assertSessionHasErrors('email');
        $this->post(route('quotations.email', $quote), ['email' => 'customer@example.com'])
            ->assertRedirect(route('quotations.show', $quote))->assertSessionHasNoErrors();
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\QuotationMail::class, function ($mail) {
            $mail->build();
            return $mail->hasTo('customer@example.com')
                && str_starts_with($mail->pdf, '%PDF-')
                && $mail->hasAttachedData($mail->pdf, 'Quotation-qt-100.pdf', ['mime' => 'application/pdf']);
        });
        $branch = Branch::create(['business_id' => $quote->business_id, 'name' => 'Other', 'code' => 'OTHER']);
        $this->withSession(['branch_id' => $branch->id])->get(route('quotations.download', $quote))->assertNotFound();
        $this->post(route('quotations.email', $quote), ['email' => 'customer@example.com'])->assertNotFound();
        $this->actingAs($this->owner())->get(route('quotations.download', $quote))->assertNotFound();
        $this->post(route('quotations.email', $quote), ['email' => 'customer@example.com'])->assertNotFound();
        \Illuminate\Support\Facades\Mail::assertSentCount(1);
    }
}
