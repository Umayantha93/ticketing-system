<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE schedules MODIFY origin VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE schedules MODIFY destination VARCHAR(255) NOT NULL');
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('origin_destination_id')->nullable()->after('destination')->constrained('destinations')->nullOnDelete();
            $table->foreignId('destination_destination_id')->nullable()->after('origin_destination_id')->constrained('destinations')->nullOnDelete();
            $table->index(['origin_destination_id', 'destination_destination_id'], 'schedules_origin_destination_ids_idx');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_origin_destination_ids_idx');
            $table->dropConstrainedForeignId('origin_destination_id');
            $table->dropConstrainedForeignId('destination_destination_id');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE schedules MODIFY origin ENUM('Kandy', 'Pettah Bus Stand', 'Kurunagala', 'Matale', 'Nuwaraeliya') NOT NULL");
            DB::statement("ALTER TABLE schedules MODIFY destination ENUM('Kandy', 'Pettah Bus Stand', 'Kurunagala', 'Matale', 'Nuwaraeliya') NOT NULL");
        }
    }
};