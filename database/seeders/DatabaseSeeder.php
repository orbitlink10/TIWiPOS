<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $businessId = \DB::table('businesses')->value('id') ?? 1;

        User::updateOrCreate([
            'email' => 'reisenseo@gmail.com',
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('admin123'),
            'business_id' => $businessId,
            'role' => 'owner',
            'is_super_admin' => true,
        ]);
    }
}
