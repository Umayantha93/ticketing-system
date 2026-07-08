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

            $buses = Bus::orderBy('id')->pluck('id')->all();
            if (empty($buses)) {
                return;
            }

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

            foreach ($buses as $index => $busId) {
                $rows = $templates[$index % count($templates)];

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
        });
    }
}
