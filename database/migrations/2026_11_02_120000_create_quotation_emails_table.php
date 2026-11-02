<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('recipient', 254);
            $table->string('sender')->nullable();
            $table->string('subject');
            $table->string('status', 20)->default('sending');
            $table->longText('content')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'branch_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_emails');
    }
};
