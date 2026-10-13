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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('tax_id')->nullable(); // NIT
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'name']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('user_id')->constrained('users');
            $table->string('invoice_number')->nullable();
            $table->string('status')->default('draft'); // draft, received, cancelled
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
            $table->integer('conversion_factor')->default(1);
            $table->decimal('quantity', 12, 2); // Cantidad de presentación comprada (ej: 5 cajas)
            $table->decimal('unit_cost', 12, 2); // Costo por presentación
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'purchase_id']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name'); // Arriendo, Servicios, Aseo, Insumos, etc.
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash'); // cash, bank, other
            $table->string('description');
            $table->string('receipt_number')->nullable();
            $table->date('expense_date');
            $table->timestamps();

            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'cash_session_id']);
        });

        Schema::create('customer_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->string('status')->default('pending'); // pending, paid
            $table->text('notes')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id', 'status']);
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_debt_id')->nullable()->constrained('customer_debts')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('customer_debts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
