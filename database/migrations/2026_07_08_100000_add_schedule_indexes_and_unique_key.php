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
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['bus_id', 'day_of_week'], 'schedules_bus_day_idx');
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
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique('schedules_unique_bus_route_day_departure');
            $table->dropIndex('schedules_bus_day_idx');
        });
    }
};
