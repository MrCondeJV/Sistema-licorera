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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_id')->nullable(); // CC / NIT
            $table->string('address')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('current_debt', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'name']);
            $table->index(['tenant_id', 'phone']);
        });

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name'); // M1, M2, Mesa Barra, etc.
            $table->string('status')->default('free'); // free, occupied, pending_payment
            $table->integer('capacity')->default(4);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('client_name')->nullable(); // "Juan", "Cumpleaños", "Amigos"
            $table->string('status')->default('open'); // open, closed, cancelled
            $table->foreignId('opened_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'table_id', 'status']);
        });

        Schema::create('account_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('presentation_name_snapshot')->default('Unidad');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->integer('conversion_factor_snapshot')->default(1);
            $table->decimal('unit_price', 12, 2); // Frozen price at addition
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_items');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('customers');
    }
};
