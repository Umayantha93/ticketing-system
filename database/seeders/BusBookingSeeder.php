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
use Illuminate\Support\Str;
use Carbon\Carbon;

class BusBookingSeeder extends Seeder
{
    public function run(): void
    {
        // Keep reseeding predictable: removing schedules cascades trips, seats, and bookings.
        Schedule::query()->delete();

        // 1. Create System Users
        $admin = User::updateOrCreate(['email' => 'admin@lankaexpress.com'], [
            'name' => 'Admin User',
            'phone_number' => '0771112223',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);

        // Create multiple bus owners
        $owner1 = User::updateOrCreate(['email' => 'owner1@lankaexpress.com'], [
            'name' => 'Anura Bus Service',
            'phone_number' => '0774445556',
            'password' => Hash::make('password123'),
            'role' => 'bus_owner'
        ]);

        $owner2 = User::updateOrCreate(['email' => 'owner2@lankaexpress.com'], [
            'name' => 'Perera Transport',
            'phone_number' => '0775556667',
            'password' => Hash::make('password123'),
            'role' => 'bus_owner'
        ]);

        $owner3 = User::updateOrCreate(['email' => 'owner3@lankaexpress.com'], [
            'name' => 'City Express Lines',
            'phone_number' => '0776667778',
            'password' => Hash::make('password123'),
            'role' => 'bus_owner'
        ]);

        User::updateOrCreate(['email' => 'passenger@lankaexpress.com'], [
            'name' => 'Nimal Silva',
            'phone_number' => '0777778889',
            'password' => Hash::make('password123'),
            'role' => 'passenger'
        ]);

        // 2. Create multiple buses for each owner

        // Owner 1 - 3 buses
        $bus1 = Bus::updateOrCreate(['bus_number_plate' => 'WP ND-4589'], [
            'user_id' => $owner1->id,
            'model' => 'Yutong Luxury A/C',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus2 = Bus::updateOrCreate(['bus_number_plate' => 'WP KA-7821'], [
            'user_id' => $owner1->id,
            'model' => 'Mercedes-Benz A/C',
            'total_seats' => 12,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        $bus3 = Bus::updateOrCreate(['bus_number_plate' => 'WP LA-3456'], [
            'user_id' => $owner1->id,
            'model' => 'Volvo Semi-Luxury',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        // Owner 2 - 2 buses
        $bus4 = Bus::updateOrCreate(['bus_number_plate' => 'CP AB-1234'], [
            'user_id' => $owner2->id,
            'model' => 'Tata Luxury',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus5 = Bus::updateOrCreate(['bus_number_plate' => 'CP CD-5678'], [
            'user_id' => $owner2->id,
            'model' => 'Ashok Leyland A/C',
            'total_seats' => 12,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        // Owner 3 - 2 buses
        $bus6 = Bus::updateOrCreate(['bus_number_plate' => 'SP EF-9012'], [
            'user_id' => $owner3->id,
            'model' => 'Scania Super Luxury',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'status' => 'active'
        ]);

        $bus7 = Bus::updateOrCreate(['bus_number_plate' => 'SP GH-3456'], [
            'user_id' => $owner3->id,
            'model' => 'MAN Express',
            'total_seats' => 12,
            'layout_type' => '2x1',
            'status' => 'active'
        ]);

        // 3. Create varied permanent schedules for each bus
        $buses = [$bus1, $bus2, $bus3, $bus4, $bus5, $bus6, $bus7];
        $schedules = [];
        $templates = [
            [
                ['Monday', 'Colombo', 'Kandy', '05:40:00', '08:55:00', 1320.00],
                ['Wednesday', 'Kandy', 'Colombo', '13:20:00', '16:35:00', 1260.00],
                ['Friday', 'Colombo', 'Kandy', '18:10:00', '21:20:00', 1490.00],
                ['Sunday', 'Kandy', 'Colombo', '06:05:00', '09:25:00', 1410.00],
            ],
            [
                ['Tuesday', 'Kandy', 'Colombo', '04:55:00', '08:00:00', 1240.00],
                ['Thursday', 'Colombo', 'Kandy', '11:35:00', '14:55:00', 1360.00],
                ['Friday', 'Kandy', 'Colombo', '17:45:00', '20:50:00', 1450.00],
                ['Saturday', 'Kandy', 'Colombo', '17:45:00', '20:50:00', 1450.00],
                ['Sunday', 'Colombo', 'Kandy', '09:15:00', '12:35:00', 1330.00],
            ],
            [
                ['Monday', 'Kandy', 'Colombo', '07:25:00', '10:40:00', 1290.00],
                ['Tuesday', 'Colombo', 'Kandy', '15:05:00', '18:20:00', 1380.00],
                ['Friday', 'Kandy', 'Colombo', '20:10:00', '23:25:00', 1520.00],
                ['Saturday', 'Colombo', 'Kandy', '06:30:00', '09:45:00', 1270.00],
            ],
            [
                ['Wednesday', 'Colombo', 'Kandy', '05:15:00', '08:30:00', 1220.00],
                ['Thursday', 'Colombo', 'Kandy', '12:55:00', '16:10:00', 1370.00],
                ['Friday', 'Colombo', 'Kandy', '16:45:00', '19:55:00', 1430.00],
                ['Sunday', 'Kandy', 'Colombo', '21:00:00', '23:59:00', 1550.00],
            ],
            [
                ['Monday', 'Colombo', 'Kandy', '10:10:00', '13:25:00', 1310.00],
                ['Tuesday', 'Kandy', 'Colombo', '14:40:00', '17:55:00', 1390.00],
                ['Saturday', 'Colombo', 'Kandy', '19:30:00', '22:40:00', 1500.00],
                ['Sunday', 'Kandy', 'Colombo', '08:35:00', '11:50:00', 1280.00],
            ],
            [
                ['Wednesday', 'Kandy', 'Colombo', '06:50:00', '10:05:00', 1340.00],
                ['Thursday', 'Colombo', 'Kandy', '09:45:00', '13:00:00', 1300.00],
                ['Friday', 'Kandy', 'Colombo', '15:25:00', '18:40:00', 1420.00],
                ['Saturday', 'Colombo', 'Kandy', '21:15:00', '23:59:00', 1560.00],
            ],
            [
                ['Monday', 'Kandy', 'Colombo', '11:20:00', '14:40:00', 1350.00],
                ['Tuesday', 'Colombo', 'Kandy', '05:55:00', '09:10:00', 1230.00],
                ['Thursday', 'Kandy', 'Colombo', '18:05:00', '21:20:00', 1470.00],
                ['Sunday', 'Colombo', 'Kandy', '16:30:00', '19:45:00', 1400.00],
            ],
        ];

        foreach ($buses as $index => $bus) {
            $rows = $templates[$index % count($templates)];

            foreach ($rows as [$dayOfWeek, $origin, $destination, $departureTime, $arrivalTime, $price]) {
                $schedules[] = Schedule::create([
                    'bus_id' => $bus->id,
                    'day_of_week' => $dayOfWeek,
                    'origin' => $origin,
                    'destination' => $destination,
                    'departure_time' => $departureTime,
                    'estimated_arrival_time' => $arrivalTime,
                    'price' => $price,
                ]);
            }
        }

        // 4. Generate one upcoming valid trip per schedule
        foreach ($schedules as $schedule) {
            $targetDate = $this->nextDateForDay($schedule->day_of_week)->format('Y-m-d');

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

        // 5. Seed bookings with seat assignments
        $passengers = User::where('role', 'passenger')->get();
        $trips = Trip::with('schedule')->orderBy('id')->take(18)->get();

        foreach ($trips as $index => $trip) {
            if ($passengers->isEmpty() || !$trip->schedule) {
                continue;
            }

            $passenger = $passengers[$index % $passengers->count()];
            $seatCount = ($index % 3) + 1; // 1 to 3 seats per booking

            $availableSeats = TripSeat::where('trip_id', $trip->id)
                ->where('status', 'available')
                ->orderBy('id')
                ->take($seatCount)
                ->get();

            if ($availableSeats->count() === 0) {
                continue;
            }

            $selectedSeatIds = $availableSeats->pluck('id');
            $selectedSeatCount = $selectedSeatIds->count();

            $booking = Booking::create([
                'user_id' => $passenger->id,
                'trip_id' => $trip->id,
                'ticket_reference' => 'TKT-' . strtoupper(Str::random(10)),
                'ticket_count' => $selectedSeatCount,
                'total_price' => (float) $trip->schedule->price * $selectedSeatCount,
                'onboarding_location' => $trip->schedule->origin . ' Main Stand',
                'status' => 'confirmed',
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);

            $booking->seats()->attach($selectedSeatIds->all());

            TripSeat::whereIn('id', $selectedSeatIds->all())
                ->update(['status' => 'booked']);
        }
    }

    private function nextDateForDay(string $dayOfWeek): Carbon
    {
        $today = Carbon::today();
        if ($today->format('l') === $dayOfWeek) {
            return $today;
        }

        return $today->next($dayOfWeek);
    }
}
