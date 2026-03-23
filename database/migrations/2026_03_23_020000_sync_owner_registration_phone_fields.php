<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('users')
            || !Schema::hasTable('businesses')
            || !Schema::hasTable('branches')
        ) {
            return;
        }

        $supportsUserPhone = Schema::hasColumn('users', 'phone');
        $supportsBusinessPhone = Schema::hasColumn('businesses', 'phone');
        $supportsBranchPhone = Schema::hasColumn('branches', 'phone');

        if (!$supportsUserPhone && !$supportsBusinessPhone && !$supportsBranchPhone) {
            return;
        }

        User::query()
            ->select(['id', 'business_id', 'branch_id', 'role'])
            ->with([
                'business:id,phone',
                'branch:id,phone',
            ])
            ->where('role', User::ROLE_OWNER)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($supportsUserPhone, $supportsBusinessPhone, $supportsBranchPhone): void {
                foreach ($users as $user) {
                    $phone = User::normalizePhone($supportsUserPhone ? $user->getAttribute('phone') : null)
                        ?? User::normalizePhone($supportsBusinessPhone ? $user->business?->phone : null)
                        ?? User::normalizePhone($supportsBranchPhone ? $user->branch?->phone : null);

                    if ($phone === null) {
                        continue;
                    }

                    if ($supportsUserPhone && User::normalizePhone($user->getAttribute('phone')) !== $phone) {
                        DB::table('users')->where('id', $user->id)->update(['phone' => $phone]);
                    }

                    if ($supportsBusinessPhone && $user->business_id && User::normalizePhone($user->business?->phone) !== $phone) {
                        DB::table('businesses')->where('id', $user->business_id)->update(['phone' => $phone]);
                    }

                    if ($supportsBranchPhone && $user->branch_id && User::normalizePhone($user->branch?->phone) !== $phone) {
                        DB::table('branches')->where('id', $user->branch_id)->update(['phone' => $phone]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op: syncing registration phone values is data repair and should not be reversed.
    }
};
