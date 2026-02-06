<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Businesses act as tenants
        // Plans table (create early so FK below succeeds)
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency', 8)->default('KES');
                $table->json('features')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('businesses', 'phone')) {
                $table->string('phone')->nullable()->after('billing_email');
            }
            if (!Schema::hasColumn('businesses', 'status')) {
                $table->string('status')->default('active')->after('phone');
            }
            if (!Schema::hasColumn('businesses', 'last_payment_at')) {
                $table->timestamp('last_payment_at')->nullable()->after('current_period_end');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
            if (!Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('is_active');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('business_id');
            }
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->string('status')->default('active')->after('interval');
            }
            if (!Schema::hasColumn('subscriptions', 'grace_until')) {
                $table->dateTime('grace_until')->nullable()->after('period_end');
            }
            if (!Schema::hasColumn('subscriptions', 'last_payment_at')) {
                $table->dateTime('last_payment_at')->nullable()->after('grace_until');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'subscription_id')) {
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete()->after('sale_id');
            }
            if (!Schema::hasColumn('payments', 'currency')) {
                $table->string('currency', 8)->default('KES')->after('amount');
            }
            if (!Schema::hasColumn('payments', 'provider')) {
                $table->string('provider')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('payments', 'provider_ref')) {
                $table->string('provider_ref')->nullable()->after('provider');
            }
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status')->default('pending')->after('provider_ref');
            }
            if (!Schema::hasColumn('payments', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('status');
            }
        });

        // Index business_id columns for scoping/performance
        foreach (['products', 'sales', 'sale_items', 'customers', 'suppliers', 'product_stocks', 'stock_movements', 'branches', 'payments'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->index('business_id');
                });
            }
        }

        // Subscription events
        if (!Schema::hasTable('subscription_events')) {
            Schema::create('subscription_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->string('event_type');
                $table->string('old_status')->nullable();
                $table->string('new_status')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Seed a default plan if none exists
        if (Schema::hasTable('plans')) {
            $exists = DB::table('plans')->count();
            if ($exists === 0) {
                DB::table('plans')->insert([
                    'name' => 'Standard',
                    'price' => env('PLAN_STANDARD_PRICE', 0),
                    'currency' => env('PLAN_CURRENCY', 'KES'),
                    'features' => json_encode(['sales' => true, 'stock' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['subscription_id', 'currency', 'provider', 'provider_ref', 'status', 'raw_payload']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['plan_id', 'status', 'grace_until', 'last_payment_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_super_admin']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['phone', 'status', 'last_payment_at']);
        });

        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('plans');
    }
};
