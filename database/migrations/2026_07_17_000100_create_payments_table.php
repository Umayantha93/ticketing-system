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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('payment_reference')->unique();
            $table->string('method')->default('card');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            $table->decimal('gross_amount', 12, 2);
            $table->decimal('base_fare_amount', 12, 2);
            $table->decimal('service_charge_amount', 12, 2);
            $table->decimal('other_charge_amount', 12, 2);

            $table->decimal('owner_payout_amount', 12, 2);
            $table->decimal('admin_service_amount', 12, 2);
            $table->decimal('admin_other_amount', 12, 2);
            $table->decimal('admin_profit_amount', 12, 2);

            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('booking_id');
            $table->index(['status', 'paid_at']);
            $table->index('trip_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};