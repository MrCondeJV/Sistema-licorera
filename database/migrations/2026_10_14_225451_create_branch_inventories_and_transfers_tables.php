<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add missing fields to branches if not present
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });

        // 2. Add branch_id to cash_sessions if not present
        if (!Schema::hasColumn('cash_sessions', 'branch_id')) {
            Schema::table('cash_sessions', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained('branches')->nullOnDelete();
                $table->index(['tenant_id', 'branch_id', 'status']);
            });
        }

        // 3. Create branch_user pivot table for explicit user assignment to branches
        if (!Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['branch_id', 'user_id']);
                $table->index(['tenant_id', 'user_id']);
            });
        }

        // 4. Create branch_inventories table for multi-branch independent stock
        if (!Schema::hasTable('branch_inventories')) {
            Schema::create('branch_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('stock', 12, 2)->default(0);
                $table->decimal('stock_min', 12, 2)->default(5);
                $table->timestamps();

                $table->unique(['branch_id', 'product_id']);
                $table->index(['tenant_id', 'branch_id']);
                $table->index(['tenant_id', 'product_id']);
            });
        }

        // 5. Create stock_transfers and stock_transfer_items for inter-branch inventory transfers
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->string('transfer_number'); // TRF-000001
                $table->string('status')->default('completed'); // pending, completed, cancelled
                $table->text('notes')->nullable();
                $table->timestamp('transferred_at')->useCurrent();
                $table->timestamps();

                $table->unique(['tenant_id', 'transfer_number']);
                $table->index(['tenant_id', 'from_branch_id']);
                $table->index(['tenant_id', 'to_branch_id']);
            });
        }

        if (!Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 12, 2);
                $table->timestamps();

                $table->index(['tenant_id', 'stock_transfer_id']);
            });
        }

        // 6. HISTORICAL DATA MIGRATION: Ensure every existing tenant has a main branch and its inventory initialized
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $t) {
            $mainBranch = DB::table('branches')
                ->where('tenant_id', $t->id)
                ->where('is_main', true)
                ->first();

            if (!$mainBranch) {
                $mainBranchId = DB::table('branches')->insertGetId([
                    'tenant_id' => $t->id,
                    'name' => 'Sede Principal',
                    'code' => 'MAIN',
                    'address' => $t->address ?? 'Calle Principal',
                    'phone' => $t->phone ?? null,
                    'is_main' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $mainBranchId = $mainBranch->id;
                if (empty($mainBranch->code)) {
                    DB::table('branches')->where('id', $mainBranchId)->update(['code' => 'MAIN']);
                }
            }

            // Associate any orphaned tables, cash registers, accounts, sales, purchases, expenses to main branch
            DB::table('tables')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('cash_registers')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('accounts')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('sales')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('purchases')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('expenses')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('cash_sessions')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('stock_movements')->where('tenant_id', $t->id)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);

            // Seed branch_inventories from existing products stock
            $products = DB::table('products')->where('tenant_id', $t->id)->get();
            foreach ($products as $p) {
                $exists = DB::table('branch_inventories')
                    ->where('branch_id', $mainBranchId)
                    ->where('product_id', $p->id)
                    ->exists();

                if (!$exists) {
                    DB::table('branch_inventories')->insert([
                        'tenant_id' => $t->id,
                        'branch_id' => $mainBranchId,
                        'product_id' => $p->id,
                        'stock' => $p->stock,
                        'stock_min' => $p->stock_min,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Assign all tenant users to the main branch
            $tenantUsers = DB::table('tenant_user')->where('tenant_id', $t->id)->get();
            foreach ($tenantUsers as $tu) {
                $assigned = DB::table('branch_user')
                    ->where('branch_id', $mainBranchId)
                    ->where('user_id', $tu->user_id)
                    ->exists();

                if (!$assigned) {
                    DB::table('branch_user')->insert([
                        'tenant_id' => $t->id,
                        'branch_id' => $mainBranchId,
                        'user_id' => $tu->user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('branch_inventories');
        Schema::dropIfExists('branch_user');

        if (Schema::hasColumn('cash_sessions', 'branch_id')) {
            Schema::table('cash_sessions', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('branches', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
