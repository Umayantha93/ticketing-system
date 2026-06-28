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

        $owner = User::create([
            'name' => 'Anura Bus Owners',
            'email' => 'owner@lankaexpress.com',
            'phone_number' => '0774445556',
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

        // 2. Create an A/C Luxury Bus
        $bus = Bus::create([
            'user_id' => $owner->id,
            'bus_number_plate' => 'WP ND-4589',
            'model' => 'Yutong Luxury A/C',
            'total_seats' => 12, // Kept small for clean testing data
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        // 3. Create Standard Timetables (Schedules)
        $scheduleToKandy = Schedule::create([
            'bus_id' => $bus->id,
            'origin' => 'Colombo',
            'destination' => 'Kandy',
            'departure_time' => '06:30:00',
            'estimated_arrival_time' => '09:30:00',
            'price' => 1200.00
        ]);

        $scheduleToColombo = Schedule::create([
            'bus_id' => $bus->id,
            'origin' => 'Kandy',
            'destination' => 'Colombo',
            'departure_time' => '14:00:00',
            'estimated_arrival_time' => '17:00:00',
            'price' => 1200.00
        ]);

        // 4. Generate Calendar Trips for the Next 3 Days
        for ($i = 0; $i < 3; $i++) {
            $targetDate = Carbon::today()->addDays($i)->format('Y-m-d');

            // Generate Morning Trip to Kandy
            $tripKandy = Trip::create([
                'schedule_id' => $scheduleToKandy->id,
                'departure_date' => $targetDate,
                'status' => 'scheduled'
            ]);

            // Generate Evening Trip back to Colombo
            $tripColombo = Trip::create([
                'schedule_id' => $scheduleToColombo->id,
                'departure_date' => $targetDate,
                'status' => 'scheduled'
            ]);

            // Automatically build the individual physical seats for both trips (A1, A2, B1, B2...)
            $seatRows = ['A', 'B', 'C'];
            foreach ([$tripKandy, $tripColombo] as $currentTrip) {
                foreach ($seatRows as $row) {
                    for ($num = 1; $num <= 4; $num++) {
                        TripSeat::create([
                            'trip_id' => $currentTrip->id,
                            'seat_number' => $row . $num,
                            'status' => 'available'
                        ]);
                    }
                }
            }
        }
    }
}