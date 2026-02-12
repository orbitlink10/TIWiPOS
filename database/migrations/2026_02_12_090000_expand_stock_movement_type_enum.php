<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `stock_movements`
            MODIFY COLUMN `type` ENUM('sale', 'sale_edit', 'purchase', 'adjustment', 'correction', 'return')
            NOT NULL DEFAULT 'adjustment'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE `stock_movements`
            MODIFY COLUMN `type` ENUM('sale', 'purchase', 'adjustment', 'return')
            NOT NULL DEFAULT 'adjustment'
        ");
    }
};

