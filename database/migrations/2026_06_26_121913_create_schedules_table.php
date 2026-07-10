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
            $table->enum('origin', ['Kandy', 'Pettah Bus Stand', 'Kurunagala', 'Matale', 'Nuwaraeliya']);
            $table->enum('destination', ['Kandy', 'Pettah Bus Stand', 'Kurunagala', 'Matale', 'Nuwaraeliya']);
            $table->time('departure_time');
            $table->time('estimated_arrival_time');
            $table->decimal('price', 8, 2);
            $table->timestamps();
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
