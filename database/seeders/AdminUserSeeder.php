<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Production / live bootstrap: creates the Bookකරා admin account only.
 *
 * Run: php artisan db:seed --class=AdminUserSeeder
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bookkara.com'],
            [
                'name' => 'Umayantha Adikarinayake',
                'phone_number' => '0711708399',
                'password' => 'Umayantha@1234',
                'role' => 'admin',
            ]
        );
    }
}
