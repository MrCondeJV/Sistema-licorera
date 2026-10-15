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
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('plan_id');
            $table->string('subscription_status')->default('trial')->after('trial_ends_at'); // trial, active, past_due, canceled
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
            $table->string('wompi_customer_email')->nullable()->after('subscription_ends_at');
            $table->string('last_payment_reference')->nullable()->after('wompi_customer_email');
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('wompi_transaction_id')->nullable()->index();
            $table->bigInteger('amount_in_cents');
            $table->string('currency')->default('COP');
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, DECLINED, VOIDED, ERROR
            $table->string('payment_method_type')->nullable(); // CARD, NEQUI, BANCOLOMBIA, PSE
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'trial_ends_at',
                'subscription_status',
                'subscription_ends_at',
                'wompi_customer_email',
                'last_payment_reference',
            ]);
        });
    }
};
