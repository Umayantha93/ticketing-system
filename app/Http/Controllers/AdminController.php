<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Booking;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function stats()
    {
        $totalUsers    = User::where('role', 'passenger')->count();
        $totalBuses    = Bus::count();
        $totalTrips    = Trip::count();
        $totalBookings = Booking::where('payment_status', 'paid')->count();

        // Platform revenue: LKR 100 per seat booked
        $platformRevenue = $totalBookings * 100;

        // Bus owner revenue: (ticket_price + 100 per seat bus owner premium)
        $busOwnerRevenue = (float) Booking::where('payment_status', 'paid')->sum('total_price');

        // Monthly breakdown (last 6 months)
        $monthlyIncome = Booking::where('payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%b') as month, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get()
            ->map(function($item) {
                $item->total = (float) $item->total;
                return $item;
            });

        // Weekly breakdown (last 4 weeks)
        $weeklyIncome = Booking::where('payment_status', 'paid')
            ->selectRaw("CONCAT('Week ', WEEK(created_at) - WEEK(NOW()) + 4) as week, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subWeeks(4))
            ->groupByRaw("WEEK(created_at)")
            ->orderByRaw("WEEK(created_at)")
            ->get()
            ->map(function($item) {
                $item->total = (float) $item->total;
                return $item;
            });

        // Bus owner balance sheets
        $busOwners = User::where('role', 'bus_owner')->with('buses')->get()->map(function ($owner) {
            $busIds    = $owner->buses->pluck('id');
            $tripIds   = Trip::whereHas('schedule', fn($q) => $q->whereIn('bus_id', $busIds))->pluck('id');
            $bookings  = Booking::whereIn('trip_id', $tripIds)->where('payment_status', 'paid');
            $totalBkgs = $bookings->count();
            $grossRev  = (float) $bookings->sum('total_price');
            $platformFee = $totalBkgs * 100;

            return [
                'id'            => $owner->id,
                'name'          => $owner->name,
                'email'         => $owner->email,
                'total_buses'   => $owner->buses->count(),
                'total_trips'   => $tripIds->count(),
                'total_bookings'=> $totalBkgs,
                'total_revenue' => $grossRev,
                'platform_fee'  => $platformFee,
                'net_earnings'  => $grossRev - $platformFee,
                'monthly_income' => Booking::whereIn('trip_id', $tripIds)
                    ->where('payment_status', 'paid')
                    ->selectRaw("DATE_FORMAT(created_at, '%b') as month, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                    ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                    ->get()
                    ->map(function($item) {
                        $item->total = (float) $item->total;
                        return $item;
                    }),
                'weekly_income' => Booking::whereIn('trip_id', $tripIds)
                    ->where('payment_status', 'paid')
                    ->selectRaw("CONCAT('Week ', WEEK(created_at) - WEEK(NOW()) + 4) as week, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
                    ->where('created_at', '>=', now()->subWeeks(4))
                    ->groupByRaw("WEEK(created_at)")
                    ->orderByRaw("WEEK(created_at)")
                    ->get()
                    ->map(function($item) {
                        $item->total = (float) $item->total;
                        return $item;
                    }),
            ];
        });

        $recentBookings = Booking::with('passenger', 'trip.schedule.bus', 'seats')
            ->where('payment_status', 'paid')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'total_users'        => $totalUsers,
            'total_buses'        => $totalBuses,
            'total_trips'        => $totalTrips,
            'total_bookings'     => $totalBookings,
            'platform_revenue'   => $platformRevenue,
            'bus_owner_revenue'  => $busOwnerRevenue,
            'monthly_income'     => $monthlyIncome,
            'weekly_income'      => $weeklyIncome,
            'bus_owners'         => $busOwners,
            'recent_bookings'    => $recentBookings,
        ]);
    }

    public function allBookings()
    {
        $bookings = Booking::with('passenger', 'trip.schedule.bus', 'seats')
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function allUsers()
    {
        $users = User::latest()->get();
        return response()->json($users);
    }

    public function allBuses()
    {
        $buses = Bus::with('busOwner')->get();
        return response()->json($buses);
    }

    public function getAllBusOwners()
    {
        $busOwners = User::where('role', 'bus_owner')->get();
        return response()->json($busOwners);
    }

    public function registerBusOwner(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:8',
        ]);

        $busOwner = User::create([
            'name'         => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'role'         => 'bus_owner',
        ]);

        return response()->json([
            'message' => 'Bus owner registered successfully',
            'bus_owner' => $busOwner
        ], 201);
    }

    public function registerBus(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'bus_number_plate' => 'required|string|unique:buses,bus_number_plate',
            'model'            => 'required|string',
            'total_seats'      => 'required|integer|min:1',
            'layout_type'      => 'required|in:2x2,2x1',
        ]);

        // Verify the user is actually a bus owner
        $owner = User::find($validated['user_id']);
        if ($owner->role !== 'bus_owner') {
            return response()->json(['message' => 'Selected user is not a bus owner'], 400);
        }

        $bus = Bus::create([
            'user_id'          => $validated['user_id'],
            'bus_number_plate' => $validated['bus_number_plate'],
            'model'            => $validated['model'],
            'total_seats'      => $validated['total_seats'],
            'layout_type'      => $validated['layout_type'],
            'status'           => 'active',
            'approval_status'  => 'approved',
        ]);

        return response()->json([
            'message' => 'Bus registered successfully',
            'bus' => $bus->load('busOwner')
        ], 201);
    }

    public function approveBus($id)
    {
        $bus = Bus::findOrFail($id);
        $bus->update(['approval_status' => 'approved']);
        
        return response()->json([
            'message' => 'Bus approved successfully',
            'bus' => $bus->load('busOwner')
        ]);
    }

    public function rejectBus($id)
    {
        $bus = Bus::findOrFail($id);
        $bus->update(['approval_status' => 'rejected']);
        
        return response()->json([
            'message' => 'Bus rejected',
            'bus' => $bus->load('busOwner')
        ]);
    }

    public function getPendingBuses()
    {
        $buses = Bus::with('busOwner')
            ->where('approval_status', 'pending')
            ->latest()
            ->get();
        
        return response()->json($buses);
    }
}
