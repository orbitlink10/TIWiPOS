<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('sku');
            }

            // Drop unique index on sku if present, to allow multiple SKUs when serial is used
            try {
                $table->dropUnique('products_sku_unique');
            } catch (\Throwable $e) {
                // ignore if it doesn't exist
            }

            $table->unique('serial_number', 'products_serial_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'serial_number')) {
                $table->dropUnique('products_serial_number_unique');
                $table->dropColumn('serial_number');
            }
            // Restore sku uniqueness
            $table->unique('sku', 'products_sku_unique');
        });
    }
};
