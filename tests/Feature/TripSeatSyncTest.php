<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripSeatSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_detail_syncs_expanded_bus_seats_and_adds_a_full_rear_row(): void
    {
        $owner = User::factory()->create([
            'phone_number' => '0710000001',
            'role' => 'bus_owner',
        ]);

        $bus = Bus::create([
            'user_id' => $owner->id,
            'bus_number_plate' => 'NC-9999',
            'phone_number' => '0710000002',
            'model' => 'Test Coach',
            'total_seats' => 12,
            'layout_type' => '2x2',
            'last_row_seats' => 4,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $schedule = Schedule::create([
            'bus_id' => $bus->id,
            'origin' => 'Kandy',
            'destination' => 'Matale',
            'day_of_week' => 'Monday',
            'departure_time' => '08:00',
            'estimated_arrival_time' => '10:00',
            'price' => 1200,
        ]);

        $trip = Trip::create([
            'schedule_id' => $schedule->id,
            'departure_date' => '2026-07-13',
            'status' => 'scheduled',
        ]);

        foreach (['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3', 'B4', 'C1', 'C2', 'C3', 'C4'] as $seatNumber) {
            TripSeat::create([
                'trip_id' => $trip->id,
                'seat_number' => $seatNumber,
                'status' => 'available',
            ]);
        }

        $bus->update(['total_seats' => 17, 'last_row_seats' => 5]);

        $response = $this->getJson("/api/trips/{$trip->id}");

        $response->assertOk();
        $response->assertJsonPath('schedule.bus.total_seats', 17);

        $seatNumbers = collect($response->json('seats'))
            ->pluck('seat_number')
            ->values()
            ->all();

        $this->assertSame(
            ['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3', 'B4', 'C1', 'C2', 'C3', 'C4', 'D1', 'D2', 'D3', 'D4', 'D5'],
            $seatNumbers,
        );

        $this->assertDatabaseCount('trip_seats', 17);
    }

    public function test_trip_search_only_returns_active_and_approved_buses(): void
    {
        $owner = User::factory()->create([
            'phone_number' => '0710000010',
            'role' => 'bus_owner',
        ]);

        $activeBus = Bus::create([
            'user_id' => $owner->id,
            'bus_number_plate' => 'NC-1111',
            'phone_number' => '0710000011',
            'model' => 'Active Coach',
            'total_seats' => 16,
            'layout_type' => '2x2',
            'last_row_seats' => 4,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $pendingBus = Bus::create([
            'user_id' => $owner->id,
            'bus_number_plate' => 'NC-2222',
            'phone_number' => '0710000012',
            'model' => 'Pending Coach',
            'total_seats' => 16,
            'layout_type' => '2x2',
            'last_row_seats' => 5,
            'status' => 'active',
            'approval_status' => 'pending',
        ]);

        Schedule::create([
            'bus_id' => $activeBus->id,
            'origin' => 'Kandy',
            'destination' => 'Matale',
            'day_of_week' => 'Monday',
            'departure_time' => '08:00',
            'estimated_arrival_time' => '10:00',
            'price' => 1200,
        ]);

        Schedule::create([
            'bus_id' => $pendingBus->id,
            'origin' => 'Kandy',
            'destination' => 'Matale',
            'day_of_week' => 'Monday',
            'departure_time' => '09:00',
            'estimated_arrival_time' => '11:00',
            'price' => 1100,
        ]);

        $response = $this->getJson('/api/trips?origin=Kandy&destination=Matale&date=2026-07-13');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.schedule.bus.bus_number_plate', 'NC-1111');
        $this->assertDatabaseMissing('buses', [
            'id' => $pendingBus->id,
            'status' => 'active',
        ]);
    }
}
