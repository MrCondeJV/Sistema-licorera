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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('discount_percentage'); // discount_percentage, fixed_price, happy_hour
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('fixed_price', 12, 2)->nullable();
            $table->time('start_time')->nullable(); // Happy hour start
            $table->time('end_time')->nullable();   // Happy hour end
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5,6,7]
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('min_quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('promotion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'promotion_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // price_change, stock_adjust, void_sale, cash_open, cash_close, permission_change, etc.
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'action']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('sync_token'); // Client-generated idempotent UUID
            $table->string('operation_type'); // sale, stock_movement, account_close
            $table->json('payload');
            $table->string('status')->default('processed'); // processed, failed, conflict
            $table->string('result_reference_type')->nullable();
            $table->unsignedBigInteger('result_reference_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sync_token']);
            $table->index(['tenant_id', 'operation_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('promotions');
    }
};
