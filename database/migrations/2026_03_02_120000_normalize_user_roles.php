<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select('id', 'role')
            ->whereNotNull('role')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $normalizedRole = strtolower(trim((string) $user->role));
                    if ($normalizedRole === (string) $user->role) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['role' => $normalizedRole]);
                }
            });
    }

    public function down(): void
    {
        // No-op: role normalization is data hygiene and safe to keep.
    }
};
