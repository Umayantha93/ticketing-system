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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            $table->string('origin');
            $table->string('destination');
            $table->unsignedBigInteger('origin_destination_id')->nullable();
            $table->unsignedBigInteger('destination_destination_id')->nullable();
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
                ->default('Monday');
            $table->time('departure_time');
            $table->time('estimated_arrival_time');
            $table->decimal('price', 8, 2);
            $table->timestamps();

            $table->index(['bus_id', 'day_of_week'], 'schedules_bus_day_idx');
            $table->index(['origin_destination_id', 'destination_destination_id'], 'schedules_origin_destination_ids_idx');
            $table->unique(
                ['bus_id', 'origin', 'destination', 'day_of_week', 'departure_time'],
                'schedules_unique_bus_route_day_departure'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
