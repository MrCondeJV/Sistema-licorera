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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name')->default('Caja Principal 01');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('opening_amount', 12, 2)->default(0); // Base en efectivo
            $table->decimal('expected_cash', 12, 2)->default(0); // Efectivo calculado
            $table->decimal('counted_cash', 12, 2)->nullable(); // Arqueo físico
            $table->decimal('difference', 12, 2)->nullable(); // Sobrante (+) o Faltante (-)
            $table->string('status')->default('open'); // open, closed
            $table->text('closing_notes')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name'); // Efectivo, Nequi, Daviplata, etc.
            $table->string('code'); // cash, nequi, daviplata, card_debit, card_credit, transfer
            $table->boolean('is_cash')->default(false); // Solo el efectivo altera arqueo físico
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('movement_type'); // in (ingreso), out (retiro), expense (gasto)
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('reason');
            $table->nullableMorphs('reference');
            $table->timestamps();

            $table->index(['tenant_id', 'cash_session_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('cash_session_id')->constrained('cash_sessions');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('sale_number'); // POS-000001
            $table->string('status')->default('completed'); // completed, refunded, partially_refunded, cancelled
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'sale_number']);
            $table->index(['tenant_id', 'status', 'sold_at']);
            $table->index(['tenant_id', 'cash_session_id']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('presentation_name_snapshot')->default('Unidad');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->integer('conversion_factor_snapshot')->default(1);
            $table->decimal('unit_cost_snapshot', 12, 2)->default(0); // Costo histórico para cálculo de margen real
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
            $table->index(['tenant_id', 'product_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods');
            $table->decimal('amount', 12, 2); // Monto efectivamente abonado a la venta
            $table->decimal('received_amount', 12, 2)->nullable(); // Ej: entregó $50.000
            $table->decimal('change_amount', 12, 2)->nullable(); // Vueltas: $15.000
            $table->string('reference')->nullable(); // Venta Nequi/Daviplata o Voucher
            $table->string('status')->default('confirmed'); // confirmed, voided
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('paid_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
            $table->index(['tenant_id', 'payment_method_id']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('reason');
            $table->decimal('total_refunded', 12, 2);
            $table->timestamp('refunded_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('refund_id')->constrained('refunds')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 12, 2);
            $table->decimal('amount_refunded', 12, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'refund_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('cash_registers');
    }
};
