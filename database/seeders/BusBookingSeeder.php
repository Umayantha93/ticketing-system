<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Bus;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BusBookingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create System Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@lankaexpress.com',
            'phone_number' => '0771112223',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create multiple bus owners
        $owner1 = User::create([
            'name' => 'Anura Bus Service',
            'email' => 'owner1@lankaexpress.com',
            'phone_number' => '0774445556',
            'password' => Hash::make('password123'),
            'role' => 'bus_owner'
        ]);

        $owner2 = User::create([
            'name' => 'Perera Transport',
            'email' => 'owner2@lankaexpress.com',
            'phone_number' => '0775556667',
            'password' => Hash::make('password123'),
            'role' => 'bus_owner'
        ]);

        $owner3 = User::create([
            'name' => 'City Express Lines',
            'email' => 'owner3@lankaexpress.com',
            'phone_number' => '0776667778',
            'password' => Hash::make('password123'),
            'role' => 'bus_owner'
        ]);

        $passenger = User::create([
            'name' => 'Nimal Silva',
            'email' => 'passenger@lankaexpress.com',
            'phone_number' => '0777778889',
            'password' => Hash::make('password123'),
            'role' => 'passenger'
        ]);

        // 2. Create multiple buses for each owner
        
        // Owner 1 - 3 buses
        $bus1 = Bus::create([
            'user_id' => $owner1->id,
            'bus_number_plate' => 'WP ND-4589',
            'model' => 'Yutong Luxury A/C',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus2 = Bus::create([
            'user_id' => $owner1->id,
            'bus_number_plate' => 'WP KA-7821',
            'model' => 'Mercedes-Benz A/C',
            'total_seats' => 12,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        $bus3 = Bus::create([
            'user_id' => $owner1->id,
            'bus_number_plate' => 'WP LA-3456',
            'model' => 'Volvo Semi-Luxury',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        // Owner 2 - 2 buses
        $bus4 = Bus::create([
            'user_id' => $owner2->id,
            'bus_number_plate' => 'CP AB-1234',
            'model' => 'Tata Luxury',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus5 = Bus::create([
            'user_id' => $owner2->id,
            'bus_number_plate' => 'CP CD-5678',
            'model' => 'Ashok Leyland A/C',
            'total_seats' => 12,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        // Owner 3 - 2 buses
        $bus6 = Bus::create([
            'user_id' => $owner3->id,
            'bus_number_plate' => 'SP EF-9012',
            'model' => 'Scania Super Luxury',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus7 = Bus::create([
            'user_id' => $owner3->id,
            'bus_number_plate' => 'SP GH-3456',
            'model' => 'MAN Express',
            'total_seats' => 12,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        // 3. Create schedules for each bus
        $buses = [$bus1, $bus2, $bus3, $bus4, $bus5, $bus6, $bus7];
        $schedules = [];

        foreach ($buses as $bus) {
            // Route to Kandy
            $schedules[] = Schedule::create([
                'bus_id' => $bus->id,
                'origin' => 'Colombo',
                'destination' => 'Kandy',
                'departure_time' => '06:30:00',
                'estimated_arrival_time' => '09:30:00',
                'price' => 1200.00
            ]);

            // Return route to Colombo
            $schedules[] = Schedule::create([
                'bus_id' => $bus->id,
                'origin' => 'Kandy',
                'destination' => 'Colombo',
                'departure_time' => '14:00:00',
                'estimated_arrival_time' => '17:00:00',
                'price' => 1200.00
            ]);
        }

        // 4. Generate Calendar Trips for the Next 3 Days
        foreach ($schedules as $schedule) {
            for ($i = 0; $i < 3; $i++) {
                $targetDate = Carbon::today()->addDays($i)->format('Y-m-d');

                $trip = Trip::create([
                    'schedule_id' => $schedule->id,
                    'departure_date' => $targetDate,
                    'status' => 'scheduled'
                ]);

                // Automatically build the individual physical seats for trip
                $seatRows = ['A', 'B', 'C'];
                foreach ($seatRows as $row) {
                    for ($num = 1; $num <= 4; $num++) {
                        TripSeat::create([
                            'trip_id' => $trip->id,
                            'seat_number' => $row . $num,
                            'status' => 'available'
                        ]);
                    }
                }
            }
        }
    }
}