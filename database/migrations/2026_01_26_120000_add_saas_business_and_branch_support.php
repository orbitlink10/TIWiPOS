<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('plan')->default('standard'); // monthly SaaS plan code
            $table->string('subscription_status')->default('active');
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('plan')->default('standard');
            $table->string('interval')->default('monthly');
            $table->string('status')->default('active');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 8)->default('KES');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
            $table->string('role')->default('owner')->after('password');
        });

        // Core business-owned tables
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->cascadeOnDelete();
        });

        // Create a default business/branch for any existing data so schema remains consistent.
        $businessId = DB::table('businesses')->insertGetId([
            'name' => 'Default Business',
            'slug' => 'default-business',
            'billing_email' => config('mail.from.address', 'support@tiwipos.test'),
            'plan' => 'standard',
            'subscription_status' => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'location' => 'Head Office',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('subscriptions')->insert([
            'business_id' => $businessId,
            'plan' => 'standard',
            'interval' => 'monthly',
            'status' => 'active',
            'amount' => 0,
            'currency' => 'KES',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Backfill existing rows.
        DB::table('users')->update(['business_id' => $businessId, 'branch_id' => $branchId]);
        foreach (['categories', 'suppliers', 'products', 'product_stocks', 'stock_movements', 'customers', 'sales', 'sale_items', 'payments'] as $table) {
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                $payload = ['business_id' => $businessId];
                if (in_array('branch_id', $columns)) {
                    $payload['branch_id'] = $branchId;
                }
                DB::table($table)->update($payload);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('business_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
