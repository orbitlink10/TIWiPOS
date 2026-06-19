<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_visits', function (Blueprint $table) {
            if (! Schema::hasColumn('service_visits', 'delivery_location')) {
                $table->string('delivery_location')->nullable()->after('customer_phone');
            }

            if (! Schema::hasColumn('service_visits', 'delivery_reference')) {
                $table->string('delivery_reference')->nullable()->after('delivery_location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_visits', function (Blueprint $table) {
            if (Schema::hasColumn('service_visits', 'delivery_location')) {
                $table->dropColumn('delivery_location');
            }

            if (Schema::hasColumn('service_visits', 'delivery_reference')) {
                $table->dropColumn('delivery_reference');
            }
        });
    }
};
