<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\Schedule;

class OwnerController extends Controller
{
    private const SERVICE_CHARGE_RATE = 0.20;
    private const OTHER_CHARGE_RATE = 0.06;
    private const CUSTOMER_CHARGE_MULTIPLIER = 1 + self::SERVICE_CHARGE_RATE + self::OTHER_CHARGE_RATE;

    public function stats()
    {
        $user    = auth()->user();
        $busIds  = Bus::where('user_id', $user->id)->pluck('id');
        $tripIds = Trip::whereHas('schedule', fn($q) => $q->whereIn('bus_id', $busIds))->pluck('id');

        $totalTrips    = $tripIds->count();
        $totalBookings = Booking::whereIn('trip_id', $tripIds)->where('payment_status', 'paid')->count();
        $grossRevenue  = (float) Booking::whereIn('trip_id', $tripIds)->where('payment_status', 'paid')->sum('total_price');
        $totalRevenue = round($this->calculateBusOwnerIncome($this->deriveBaseFare($grossRevenue)), 2);

        $monthlyIncome = Booking::whereIn('trip_id', $tripIds)
            ->where('payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period_key, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get()
            ->map(function($item) {
                $ownerIncome = $this->calculateBusOwnerIncome($this->deriveBaseFare((float) $item->total));
                $item->month = \Carbon\Carbon::createFromFormat('Y-m', $item->period_key)->format('M');
                $item->total = round($ownerIncome, 2);
                unset($item->period_key);
                return $item;
            });

        $weeklyIncome = Booking::whereIn('trip_id', $tripIds)
            ->where('payment_status', 'paid')
            ->selectRaw("WEEK(created_at, 1) as week_num, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subWeeks(4))
            ->groupByRaw("WEEK(created_at, 1)")
            ->orderByRaw("WEEK(created_at, 1)")
            ->get()
            ->map(function($item, $index) {
                $ownerIncome = $this->calculateBusOwnerIncome($this->deriveBaseFare((float) $item->total));
                $item->week = 'Week ' . ($index + 1);
                $item->total = round($ownerIncome, 2);
                unset($item->week_num);
                return $item;
            });

        $recentBookings = Booking::whereIn('trip_id', $tripIds)
            ->with('passenger', 'trip.schedule.bus', 'seats')
            ->where('payment_status', 'paid')
            ->latest()
            ->take(10)
            ->get()
            ->map(function (Booking $booking) {
                $booking->total_price = round(
                    $this->calculateBusOwnerIncome($this->deriveBaseFare((float) $booking->total_price)),
                    2
                );

                return $booking;
            });

        $buses = Bus::where('user_id', $user->id)->get();

        return response()->json([
            'total_trips'    => $totalTrips,
            'total_bookings' => $totalBookings,
            'total_revenue'  => $totalRevenue,
            'gross_revenue'  => round($grossRevenue, 2),
            'weekly_income'  => $weeklyIncome,
            'monthly_income' => $monthlyIncome,
            'recent_bookings'=> $recentBookings,
            'buses'          => $buses,
        ]);
    }

    public function bookings(Request $request)
    {
        $user = auth()->user();
        $busIds = Bus::where('user_id', $user->id)->pluck('id');

        // Filter by bus_id if provided
        $busIdFilter = $request->query('bus_id');
        if ($busIdFilter) {
            // Verify the bus belongs to the owner
            if (!$busIds->contains($busIdFilter)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $busIds = collect([$busIdFilter]);
        }

        $tripIds = Trip::whereHas('schedule', fn($q) => $q->whereIn('bus_id', $busIds))->pluck('id');

        $bookings = Booking::whereIn('trip_id', $tripIds)
            ->with('passenger', 'trip.schedule.bus', 'seats')
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                $booking->total_price = round(
                    $this->calculateBusOwnerIncome($this->deriveBaseFare((float) $booking->total_price)),
                    2
                );

                return $booking;
            });

        return response()->json($bookings);
    }

    private function deriveBaseFare(float $grossAmount): float
    {
        if ($grossAmount <= 0) {
            return 0;
        }

        return $grossAmount / self::CUSTOMER_CHARGE_MULTIPLIER;
    }

    private function calculateBusOwnerIncome(float $baseFare): float
    {
        return $baseFare * (1 + (self::SERVICE_CHARGE_RATE / 2));
    }

    public function buses()
    {
        $buses = Bus::where('user_id', auth()->id())->get();
        return response()->json($buses);
    }

    public function schedules(Request $request)
    {
        $user = auth()->user();
        $busIds = Bus::where('user_id', $user->id)->pluck('id');
        $requestedBusId = $request->query('bus_id');

        if ($requestedBusId && !$busIds->contains((int) $requestedBusId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Schedule::with('bus')
            ->whereIn('bus_id', $busIds)
            ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->orderBy('departure_time');

        if ($requestedBusId) {
            $query->where('bus_id', $requestedBusId);
        }

        return response()->json($query->get());
    }
}
