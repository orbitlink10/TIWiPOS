<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'reisenseo@gmail.com')
            ->where('is_super_admin', true)
            ->update([
                'is_super_admin' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'reisenseo@gmail.com')
            ->update([
                'is_super_admin' => true,
                'updated_at' => now(),
            ]);
    }
};
