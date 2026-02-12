<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->cascadeOnDelete();
                $table->index(['branch_id', 'sale_id']);
            }
        });

        DB::statement('
            UPDATE sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            SET si.branch_id = s.branch_id
            WHERE si.branch_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'branch_id')) {
                $table->dropIndex('sale_items_branch_id_sale_id_index');
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};

