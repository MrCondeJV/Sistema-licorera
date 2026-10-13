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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'sort_order']);
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_buy', 12, 2)->default(0); // Costo unitario promedio
            $table->decimal('price_sell', 12, 2)->default(0); // Precio de venta base
            $table->decimal('stock', 12, 2)->default(0); // Unidades base disponibles
            $table->decimal('stock_min', 12, 2)->default(5);
            $table->string('base_unit')->default('unidad'); // unidad, botella
            $table->boolean('is_beer')->default(false); // Cervezas permiten conversiones (1x, 6x, 24x)
            $table->boolean('is_liquor')->default(false); // Licores: estrictamente botella completa, no fraccionamiento
            $table->decimal('tax_percentage', 5, 2)->default(0.00); // Impuesto (IVA o consumo)
            $table->string('image')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'category_id', 'is_active']);
            $table->index(['tenant_id', 'barcode']);
        });

        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name'); // Unidad, Six-Pack, Caja 24
            $table->integer('conversion_factor')->default(1); // Unidades base descontadas
            $table->decimal('sale_price', 12, 2);
            $table->string('barcode')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'is_active']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type'); // in, out, sale, purchase, adjustment, return, count_diff
            $table->decimal('quantity', 12, 2); // Unidades base (+ o -)
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->string('reason');
            $table->nullableMorphs('reference'); // sale, purchase, inventory_count
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'created_at']);
            $table->index(['tenant_id', 'movement_type']);
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('status')->default('pending'); // pending, applied, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('system_stock', 12, 2);
            $table->decimal('physical_stock', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'inventory_count_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_presentations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
