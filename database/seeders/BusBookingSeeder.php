<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Bus;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
            'total_seats' => 24,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus2 = Bus::create([
            'user_id' => $owner1->id,
            'bus_number_plate' => 'WP KA-7821',
            'model' => 'Mercedes-Benz A/C',
            'total_seats' => 21,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        $bus3 = Bus::create([
            'user_id' => $owner1->id,
            'bus_number_plate' => 'WP LA-3456',
            'model' => 'Volvo Semi-Luxury',
            'total_seats' => 32,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        // Owner 2 - 2 buses
        $bus4 = Bus::create([
            'user_id' => $owner2->id,
            'bus_number_plate' => 'CP AB-1234',
            'model' => 'Tata Luxury',
            'total_seats' => 28,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus5 = Bus::create([
            'user_id' => $owner2->id,
            'bus_number_plate' => 'CP CD-5678',
            'model' => 'Ashok Leyland A/C',
            'total_seats' => 18,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        // Owner 3 - 2 buses
        $bus6 = Bus::create([
            'user_id' => $owner3->id,
            'bus_number_plate' => 'SP EF-9012',
            'model' => 'Scania Super Luxury',
            'total_seats' => 36,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus7 = Bus::create([
            'user_id' => $owner3->id,
            'bus_number_plate' => 'SP GH-3456',
            'model' => 'MAN Express',
            'total_seats' => 15,
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
        $allTrips = [];
        foreach ($schedules as $schedule) {
            for ($i = 0; $i < 3; $i++) {
                $targetDate = Carbon::today()->addDays($i)->format('Y-m-d');

                $trip = Trip::create([
                    'schedule_id' => $schedule->id,
                    'departure_date' => $targetDate,
                    'status' => 'scheduled'
                ]);

                $allTrips[] = $trip;

                // Automatically build the individual physical seats based on bus total_seats
                $bus = $schedule->bus;
                $totalSeats = $bus->total_seats;
                $layoutType = $bus->layout_type;

                // Calculate rows and columns based on layout
                if ($layoutType === '2x1') {
                    // 2x1 layout: 3 seats per row (2 left + 1 right)
                    $seatsPerRow = 3;
                } else {
                    // 2x2 layout: 4 seats per row (2 left + 2 right)
                    $seatsPerRow = 4;
                }

                $numRows = ceil($totalSeats / $seatsPerRow);
                $seatRows = range('A', chr(ord('A') + $numRows - 1));

                $seatCounter = 0;
                foreach ($seatRows as $row) {
                    for ($num = 1; $num <= $seatsPerRow; $num++) {
                        if ($seatCounter >= $totalSeats) {
                            break 2; // Stop if we've created all seats
                        }

                        TripSeat::create([
                            'trip_id' => $trip->id,
                            'seat_number' => $row . $num,
                            'status' => 'available'
                        ]);

                        $seatCounter++;
                    }
                }
            }
        }

        // 5. Create fake bookings for today and tomorrow
        $today = Carbon::today()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        // Filter trips for today and tomorrow
        $todayAndTomorrowTrips = Trip::whereIn('departure_date', [$today, $tomorrow])
            ->with('seats')
            ->get();

        $bookingCounter = 1;
        foreach ($todayAndTomorrowTrips as $trip) {
            // Create 1-3 random bookings per trip
            $numBookings = rand(1, 3);

            for ($b = 0; $b < $numBookings; $b++) {
                // Get available seats for this trip
                $availableSeats = TripSeat::where('trip_id', $trip->id)
                    ->where('status', 'available')
                    ->get();

                if ($availableSeats->count() < 1) {
                    continue; // No seats available
                }

                // Random number of seats (1-3)
                $seatCount = min(rand(1, 3), $availableSeats->count());
                $selectedSeats = $availableSeats->random($seatCount);

                // Get trip price
                $price = $trip->schedule->price;
                $totalPrice = $price * $seatCount;

                // Create booking
                $booking = Booking::create([
                    'user_id' => $passenger->id,
                    'trip_id' => $trip->id,
                    'ticket_reference' => 'TKT-' . strtoupper(substr(md5($bookingCounter . time()), 0, 8)),
                    'ticket_count' => $seatCount,
                    'total_price' => $totalPrice,
                    'status' => 'confirmed',
                    'payment_method' => ['card', 'cash', 'online'][rand(0, 2)],
                    'payment_status' => 'paid',
                ]);

                // Link seats to booking and mark them as booked
                foreach ($selectedSeats as $seat) {
                    DB::table('booking_seat')->insert([
                        'booking_id' => $booking->id,
                        'trip_seat_id' => $seat->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $seat->update(['status' => 'booked']);
                }

                $bookingCounter++;
            }
        }
    }
}
