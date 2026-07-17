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
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('phone_number');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_branch');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_branch',
                'bank_account_name',
                'bank_account_number',
            ]);
        });
    }
};
