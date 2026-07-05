<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Booking;

class OwnerController extends Controller
{
    public function stats()
    {
        $user    = auth()->user();
        $busIds  = Bus::where('user_id', $user->id)->pluck('id');
        $tripIds = Trip::whereHas('schedule', fn($q) => $q->whereIn('bus_id', $busIds))->pluck('id');

        $totalTrips    = $tripIds->count();
        $totalBookings = Booking::whereIn('trip_id', $tripIds)->where('payment_status', 'paid')->count();
        $totalRevenue  = (float) Booking::whereIn('trip_id', $tripIds)->where('payment_status', 'paid')->sum('total_price');

        $monthlyIncome = Booking::whereIn('trip_id', $tripIds)
            ->where('payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%b') as month, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get()
            ->map(function($item) {
                $item->total = (float) $item->total;
                return $item;
            });

        $weeklyIncome = Booking::whereIn('trip_id', $tripIds)
            ->where('payment_status', 'paid')
            ->selectRaw("CONCAT('Week ', WEEK(created_at) - WEEK(NOW()) + 4) as week, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subWeeks(4))
            ->groupByRaw("WEEK(created_at)")
            ->orderByRaw("WEEK(created_at)")
            ->get()
            ->map(function($item) {
                $item->total = (float) $item->total;
                return $item;
            });

        $recentBookings = Booking::whereIn('trip_id', $tripIds)
            ->with('passenger', 'trip.schedule.bus', 'seats')
            ->where('payment_status', 'paid')
            ->latest()
            ->take(10)
            ->get();

        $buses = Bus::where('user_id', $user->id)->get();

        return response()->json([
            'total_trips'    => $totalTrips,
            'total_bookings' => $totalBookings,
            'total_revenue'  => $totalRevenue,
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
            ->get();

        return response()->json($bookings);
    }

    public function buses()
    {
        $buses = Bus::where('user_id', auth()->id())->get();
        return response()->json($buses);
    }
}
