<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('activity');
            $table->date('activity_date');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->index(['business_id', 'user_id', 'activity_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_items');
    }
};
