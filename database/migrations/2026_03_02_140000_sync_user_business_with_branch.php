<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $branchBusinessById = DB::table('branches')
            ->select('id', 'business_id')
            ->get()
            ->pluck('business_id', 'id');

        DB::table('users')
            ->select('id', 'business_id', 'branch_id')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($branchBusinessById) {
                foreach ($users as $user) {
                    $branchBusinessId = (int) ($branchBusinessById[$user->branch_id] ?? 0);
                    if ($branchBusinessId <= 0) {
                        continue;
                    }

                    if ((int) $user->business_id === $branchBusinessId) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['business_id' => $branchBusinessId]);
                }
            });
    }

    public function down(): void
    {
        // No-op: this migration repairs tenant consistency in production data.
    }
};
