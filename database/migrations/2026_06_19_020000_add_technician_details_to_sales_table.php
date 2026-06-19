<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('technician_name')->nullable()->after('customer_location');
            $table->string('technician_phone', 50)->nullable()->after('technician_name');
            $table->string('installation_location')->nullable()->after('technician_phone');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['technician_name', 'technician_phone', 'installation_location']);
        });
    }
};
