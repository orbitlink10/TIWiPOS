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

        $superAdmin = User::firstOrNew([
            'email' => 'reisenseo@gmail.com',
        ]);

        if (!$superAdmin->exists) {
            $superAdmin->name = 'Super Admin';
            $superAdmin->password = bcrypt('admin123');
            $superAdmin->email_verified_at = now();
        }

        if (!$superAdmin->business_id) {
            $superAdmin->business_id = $businessId;
        }

        if (!$superAdmin->branch_id) {
            $superAdmin->branch_id = $branchId;
        }

        if (blank($superAdmin->name)) {
            $superAdmin->name = 'Super Admin';
        }

        $superAdmin->role = User::ROLE_OWNER;
        $superAdmin->is_active = true;
        $superAdmin->is_super_admin = true;
        $superAdmin->save();
    }
}
