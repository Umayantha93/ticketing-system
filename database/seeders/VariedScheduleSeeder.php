<?php

namespace Database\Seeders;

use App\Models\Bus;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariedScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Deleting schedules cascades to trips, trip_seats and bookings via FKs.
            Schedule::query()->delete();

            $buses = Bus::query()
                ->get()
                ->sortBy('id')
                ->values()
                ->map(fn (Bus $bus) => $bus->id)
                ->all();
            if (empty($buses)) {
                return;
            }

            $routes = [
                ['Monday', 'Kandy', 'Pettah Bus Stand', '05:40:00', '08:55:00', 1320.00],
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

            foreach ($buses as $index => $busId) {
                $rows = [];

                for ($offset = 0; $offset < 4; $offset++) {
                    $rows[] = $routes[($index * 4 + $offset) % count($routes)];
                }

                foreach ($rows as [$day, $origin, $destination, $departure, $arrival, $price]) {
                    Schedule::create([
                        'bus_id' => $busId,
                        'day_of_week' => $day,
                        'origin' => $origin,
                        'destination' => $destination,
                        'departure_time' => $departure,
                        'estimated_arrival_time' => $arrival,
                        'price' => $price,
                    ]);
                }
            }

            $defaultBusId = $buses[0] ?? null;
            if ($defaultBusId) {
                $dailyCorridor = [
                    ['Monday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Tuesday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Wednesday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Thursday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Friday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Saturday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Sunday', 'Kandy', 'Pettah Bus Stand', '06:30:00', '09:45:00', 1320.00],
                    ['Monday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                    ['Tuesday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                    ['Wednesday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                    ['Thursday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                    ['Friday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                    ['Saturday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                    ['Sunday', 'Pettah Bus Stand', 'Kandy', '14:30:00', '17:45:00', 1290.00],
                ];

                foreach ($dailyCorridor as [$day, $origin, $destination, $departure, $arrival, $price]) {
                    Schedule::updateOrCreate(
                        [
                            'bus_id' => $defaultBusId,
                            'day_of_week' => $day,
                            'origin' => $origin,
                            'destination' => $destination,
                            'departure_time' => $departure,
                        ],
                        [
                            'estimated_arrival_time' => $arrival,
                            'price' => $price,
                        ]
                    );
                }
            }
        });
    }
}
