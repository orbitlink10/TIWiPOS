<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $email = 'reisenseo@gmail.com';
        $businessId = DB::table('businesses')->orderBy('id')->value('id');
        $branchId = $businessId
            ? DB::table('branches')
                ->where('business_id', $businessId)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id')
            : null;

        $user = DB::table('users')->where('email', $email)->first();

        if ($user) {
            $updates = [
                'role' => 'owner',
                'is_active' => true,
                'is_super_admin' => true,
                'updated_at' => now(),
            ];

            if (empty($user->business_id) && $businessId) {
                $updates['business_id'] = $businessId;
            }

            if (empty($user->branch_id) && $branchId) {
                $updates['branch_id'] = $branchId;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update($updates);

            return;
        }

        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'role' => 'owner',
            'is_active' => true,
            'is_super_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No-op: keeping the promoted super admin account is safer than silently removing access.
    }
};
