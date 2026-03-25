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
        $businessId = \DB::table('businesses')->orderBy('id')->value('id');
        $branchId = $businessId
            ? \DB::table('branches')
                ->where('business_id', $businessId)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id')
            : null;

        $user = User::firstOrNew([
            'email' => 'reisenseo@gmail.com',
        ]);

        if (!$user->exists) {
            $user->name = 'Super Admin';
            $user->password = bcrypt('admin123');
            $user->email_verified_at = now();
        }

        if (!$user->business_id) {
            $user->business_id = $businessId;
        }

        if (!$user->branch_id) {
            $user->branch_id = $branchId;
        }

        if (blank($user->name)) {
            $user->name = 'Super Admin';
        }

        $user->role = User::ROLE_OWNER;
        $user->is_active = true;
        $user->is_super_admin = false;
        $user->save();
    }
}
