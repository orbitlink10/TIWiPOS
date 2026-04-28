<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_worker_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('service_worker_id')->constrained('service_workers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_id', 'service_worker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_worker_service');
    }
};
