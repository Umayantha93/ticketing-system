<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Bus;
use App\Models\Destination;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BusBookingSeeder extends Seeder
{
    private const SERVICE_CHARGE_RATE = 0.20;
    private const OTHER_CHARGE_RATE = 0.06;

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
            'phone_number' => '0774445556',
            'model' => 'Yutong Luxury A/C',
            'total_seats' => 13,
            'layout_type' => '2x2',
            'last_row_seats' => 5,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $bus2 = Bus::updateOrCreate(['bus_number_plate' => 'WP KA-7821'], [
            'user_id' => $owner1->id,
            'phone_number' => '0774445556',
            'model' => 'Mercedes-Benz A/C',
            'total_seats' => 14,
            'layout_type' => '1x2',
            'last_row_seats' => 5,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $bus3 = Bus::updateOrCreate(['bus_number_plate' => 'WP LA-3456'], [
            'user_id' => $owner1->id,
            'phone_number' => '0774445556',
            'model' => 'Volvo Semi-Luxury',
            'total_seats' => 16,
            'layout_type' => '2x2',
            'last_row_seats' => 4,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // Owner 2 - 2 buses
        $bus4 = Bus::updateOrCreate(['bus_number_plate' => 'CP AB-1234'], [
            'user_id' => $owner2->id,
            'phone_number' => '0775556667',
            'model' => 'Tata Luxury',
            'total_seats' => 15,
            'layout_type' => '2x2',
            'last_row_seats' => 5,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $bus5 = Bus::updateOrCreate(['bus_number_plate' => 'CP CD-5678'], [
            'user_id' => $owner2->id,
            'phone_number' => '0775556667',
            'model' => 'Ashok Leyland A/C',
            'total_seats' => 11,
            'layout_type' => '1x3',
            'last_row_seats' => 5,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // Owner 3 - 2 buses
        $bus6 = Bus::updateOrCreate(['bus_number_plate' => 'SP EF-9012'], [
            'user_id' => $owner3->id,
            'phone_number' => '0776667778',
            'model' => 'Scania Super Luxury',
            'total_seats' => 18,
            'layout_type' => '2x2',
            'last_row_seats' => 6,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $bus7 = Bus::updateOrCreate(['bus_number_plate' => 'SP GH-3456'], [
            'user_id' => $owner3->id,
            'phone_number' => '0776667778',
            'model' => 'MAN Express',
            'total_seats' => 12,
            'layout_type' => '2x3',
            'last_row_seats' => 4,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // 3. Create varied permanent schedules for each bus
        $buses = [$bus1, $bus2, $bus3, $bus4, $bus5, $bus6, $bus7];
        $schedules = [];
        $destinationMap = collect();
        Destination::query()->get()->each(function (Destination $destination) use (&$destinationMap) {
            $destinationMap->put($destination->name_en, $destination);

            foreach ($destination->aliases ?? [] as $alias) {
                $destinationMap->put((string) $alias, $destination);
            }
        });
        $routes = [
            ['Monday', 'Kandy', 'Pettah Bus Stand', '05:40:00', '08:55:00', 1000.00],
            ['Tuesday', 'Pettah Bus Stand', 'Kandy', '13:20:00', '16:35:00', 1260.00],
            ['Wednesday', 'Kandy', 'Kurunagala', '18:10:00', '21:20:00', 1490.00],
            ['Thursday', 'Kurunagala', 'Kandy', '06:05:00', '09:25:00', 1410.00],
            ['Friday', 'Kandy', 'Matale', '04:55:00', '08:00:00', 1240.00],
            ['Saturday', 'Matale', 'Kandy', '11:35:00', '14:55:00', 1360.00],
            ['Sunday', 'Kandy', 'Nuwaraeliya', '17:45:00', '20:50:00', 1450.00],
            ['Monday', 'Nuwaraeliya', 'Kandy', '09:15:00', '12:35:00', 1330.00],
            ['Tuesday', 'Pettah Bus Stand', 'Kurunagala', '07:25:00', '10:40:00', 1290.00],
            ['Wednesday', 'Kurunagala', 'Pettah Bus Stand', '15:05:00', '18:20:00', 1380.00],
            ['Thursday', 'Pettah Bus Stand', 'Matale', '20:10:00', '23:25:00', 1520.00],
            ['Friday', 'Matale', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1270.00],
            ['Saturday', 'Pettah Bus Stand', 'Nuwaraeliya', '05:15:00', '08:30:00', 1220.00],
            ['Sunday', 'Nuwaraeliya', 'Pettah Bus Stand', '12:55:00', '16:10:00', 1370.00],
            ['Monday', 'Kurunagala', 'Matale', '16:45:00', '19:55:00', 1430.00],
            ['Tuesday', 'Matale', 'Kurunagala', '21:00:00', '23:59:00', 1550.00],
            ['Wednesday', 'Kurunagala', 'Nuwaraeliya', '10:10:00', '13:25:00', 1310.00],
            ['Thursday', 'Nuwaraeliya', 'Kurunagala', '14:40:00', '17:55:00', 1390.00],
            ['Friday', 'Matale', 'Nuwaraeliya', '19:30:00', '22:40:00', 1500.00],
            ['Saturday', 'Nuwaraeliya', 'Matale', '08:35:00', '11:50:00', 1280.00],
        ];

        foreach ($buses as $index => $bus) {
            $rows = [];

            for ($offset = 0; $offset < 4; $offset++) {
                $rows[] = $routes[($index * 4 + $offset) % count($routes)];
            }

            foreach ($rows as [$dayOfWeek, $origin, $destination, $departureTime, $arrivalTime, $price]) {
                $originDestination = $destinationMap->get($origin);
                $targetDestination = $destinationMap->get($destination);

                if (!$originDestination || !$targetDestination) {
                    continue;
                }

                $schedules[] = Schedule::create([
                    'bus_id' => $bus->id,
                    'day_of_week' => $dayOfWeek,
                    'origin' => $originDestination->name_en,
                    'destination' => $targetDestination->name_en,
                    'origin_destination_id' => $originDestination->id,
                    'destination_destination_id' => $targetDestination->id,
                    'departure_time' => $departureTime,
                    'estimated_arrival_time' => $arrivalTime,
                    'price' => $price,
                ]);
            }
        }

        $defaultBus = $buses[0] ?? null;
        if ($defaultBus) {
            $dailyCorridor = [
                ['Monday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Tuesday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Wednesday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Thursday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Friday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Saturday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Sunday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1000.00],
                ['Monday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ['Tuesday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ['Wednesday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ['Thursday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ['Friday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ['Saturday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ['Sunday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
            ];

            foreach ($dailyCorridor as [$dayOfWeek, $origin, $destination, $departureTime, $arrivalTime, $price]) {
                $originDestination = $destinationMap->get($origin);
                $targetDestination = $destinationMap->get($destination);

                if (!$originDestination || !$targetDestination) {
                    continue;
                }

                Schedule::updateOrCreate(
                    [
                        'bus_id' => $defaultBus->id,
                        'day_of_week' => $dayOfWeek,
                        'origin' => $originDestination->name_en,
                        'destination' => $targetDestination->name_en,
                        'origin_destination_id' => $originDestination->id,
                        'destination_destination_id' => $targetDestination->id,
                        'departure_time' => $departureTime,
                    ],
                    [
                        'estimated_arrival_time' => $arrivalTime,
                        'price' => $price,
                    ]
                );
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

            foreach ($this->buildSeatNumbers($schedule->bus) as $seatNumber) {
                TripSeat::create([
                    'trip_id' => $trip->id,
                    'seat_number' => $seatNumber,
                    'status' => 'available'
                ]);
            }
        }

        // 5. Seed bookings with seat assignments
        $passengers = User::query()
            ->get()
            ->filter(fn (User $user) => $user->role === 'passenger')
            ->values();
        $trips = Trip::with('schedule')->orderBy('id')->take(18)->get();

        foreach ($trips as $index => $trip) {
            if ($passengers->isEmpty() || !$trip->schedule) {
                continue;
            }

            $passenger = $passengers[$index % $passengers->count()];
            $seatCount = ($index % 3) + 1; // 1 to 3 seats per booking

            $availableSeats = TripSeat::query()
                ->get()
                ->filter(fn (TripSeat $seat) => $seat->trip_id === $trip->id && $seat->status === 'available')
                ->sortBy('id')
                ->take($seatCount)
                ->values();

            if ($availableSeats->count() === 0) {
                continue;
            }

            $selectedSeatIds = $availableSeats->map(fn (TripSeat $seat) => $seat->id);
            $selectedSeatCount = $selectedSeatIds->count();

            $basePrice = (float) $trip->schedule->price;
            $pricePerSeat = $basePrice
                + ($basePrice * self::SERVICE_CHARGE_RATE)
                + ($basePrice * self::OTHER_CHARGE_RATE);

            $booking = Booking::create([
                'user_id' => $passenger->id,
                'trip_id' => $trip->id,
                'ticket_reference' => 'TKT-' . strtoupper(Str::random(10)),
                'ticket_count' => $selectedSeatCount,
                'total_price' => round($pricePerSeat * $selectedSeatCount, 2),
                'onboarding_location' => $trip->schedule->origin . ' Main Stand',
                'status' => 'confirmed',
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);

            $booking->seats()->attach($selectedSeatIds->all());

            foreach ($selectedSeatIds->all() as $seatId) {
                TripSeat::query()
                    ->where('id', $seatId)
                    ->update(['status' => 'booked']);
            }
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

    private function buildSeatNumbers(Bus $bus): array
    {
        $seatNumbers = [];
        [$left, $right] = array_pad(explode('x', $bus->layout_type), 2, '0');
        $standardRowCapacity = max(1, ((int) $left) + ((int) $right));
        $rearRowSeats = min(max(1, (int) $bus->last_row_seats), (int) $bus->total_seats);
        $frontSectionSeats = (int) $bus->total_seats - $rearRowSeats;
        $rowCounts = [];

        if ($frontSectionSeats > 0) {
            $frontRowCount = max(1, (int) ceil($frontSectionSeats / $standardRowCapacity));
            $baseSeatsPerRow = intdiv($frontSectionSeats, $frontRowCount);
            $extraSeats = $frontSectionSeats % $frontRowCount;

            for ($rowIndex = 0; $rowIndex < $frontRowCount; $rowIndex++) {
                $rowCounts[] = $baseSeatsPerRow + ($rowIndex < $extraSeats ? 1 : 0);
            }
        }

        $rowCounts[] = $rearRowSeats;

        foreach (array_values(array_filter($rowCounts)) as $rowIndex => $seatCount) {
            $rowLabel = chr(65 + $rowIndex);

            for ($seatNumber = 1; $seatNumber <= $seatCount; $seatNumber++) {
                $seatNumbers[] = $rowLabel . $seatNumber;
            }
        }

        return $seatNumbers;
    }
}
