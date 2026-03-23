<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegisterWithoutPhoneColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_still_works_when_phone_column_is_missing(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        $response = $this->post(route('register.store'), [
            'name' => 'James Kimani',
            'email' => 'info@spacelinkkenya.co.ke',
            'phone' => '0714804532',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Spacelink Kenya',
            'branch_name' => 'Main Branch',
            'industry' => 'Technology',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'info@spacelinkkenya.co.ke',
            'name' => 'James Kimani',
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('businesses', [
            'name' => 'Spacelink Kenya',
            'phone' => '0714804532',
        ]);
    }
}
