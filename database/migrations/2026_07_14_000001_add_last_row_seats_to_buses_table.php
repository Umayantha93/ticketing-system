<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->unsignedTinyInteger('last_row_seats')->default(4)->after('layout_type');
        });

        DB::table('buses')
            ->whereNot('approval_status', 'approved')
            ->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropColumn('last_row_seats');
        });
    }
};