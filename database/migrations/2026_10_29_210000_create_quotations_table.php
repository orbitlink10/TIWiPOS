<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 80);
            $table->string('customer_name');
            $table->date('quotation_date');
            $table->date('valid_until');
            $table->json('document');
            $table->timestamps();
            $table->unique(['business_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
