<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    private const SERVICE_CHARGE_RATE = 0.20;
    private const OTHER_CHARGE_RATE = 0.06;
    private const CUSTOMER_CHARGE_MULTIPLIER = 1 + self::SERVICE_CHARGE_RATE + self::OTHER_CHARGE_RATE;

    public function stats()
    {
        $totalUsers    = User::where('role', 'passenger')->count();
        $totalBuses    = Bus::count();
        $totalTrips    = Trip::count();
        $totalBookings = Booking::where('payment_status', 'paid')->count();

        $grossRevenue = (float) Booking::where('payment_status', 'paid')->sum('total_price');
        $baseFareRevenue = $this->deriveBaseFare($grossRevenue);
        $busOwnerRevenue = $this->calculateBusOwnerIncome($baseFareRevenue);
        $adminServiceRevenue = $this->calculateAdminServiceIncome($baseFareRevenue);
        $adminOtherRevenue = $this->calculateAdminOtherIncome($baseFareRevenue);
        $adminProfit = $adminServiceRevenue + $adminOtherRevenue;

        // Monthly breakdown (last 6 months)
        $monthlyIncome = Booking::where('payment_status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period_key, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->get()
            ->map(function($item) {
                $item->month = \Carbon\Carbon::createFromFormat('Y-m', $item->period_key)->format('M');
                $item->total = (float) $item->total;
                unset($item->period_key);
                return $item;
            });

        // Weekly breakdown (last 4 weeks)
        $weeklyIncome = Booking::where('payment_status', 'paid')
            ->selectRaw("WEEK(created_at, 1) as week_num, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
            ->where('created_at', '>=', now()->subWeeks(4))
            ->groupByRaw("WEEK(created_at, 1)")
            ->orderByRaw("WEEK(created_at, 1)")
            ->get()
            ->map(function($item, $index) {
                $item->week = 'Week ' . ($index + 1);
                $item->total = (float) $item->total;
                unset($item->week_num);
                return $item;
            });

        $weeklyLedger = $weeklyIncome->map(function ($item) {
            $weekGross = (float) $item->total;
            $weekBaseFare = $this->deriveBaseFare($weekGross);
            $weekBusOwnerPayout = $this->calculateBusOwnerIncome($weekBaseFare);
            $weekAdminService = $this->calculateAdminServiceIncome($weekBaseFare);
            $weekAdminOther = $this->calculateAdminOtherIncome($weekBaseFare);

            return [
                'week' => $item->week,
                'bookings' => (int) $item->bookings,
                'gross_income' => round($weekGross, 2),
                'bus_owner_payout' => round($weekBusOwnerPayout, 2),
                'admin_service_income' => round($weekAdminService, 2),
                'admin_other_income' => round($weekAdminOther, 2),
                'admin_profit' => round($weekAdminService + $weekAdminOther, 2),
            ];
        });

        // Bus owner balance sheets
        $busOwners = User::where('role', 'bus_owner')->with('buses')->get()->map(function ($owner) {
            $busIds    = $owner->buses->pluck('id');

            if ($busIds->isEmpty()) {
                return [
                    'id'            => $owner->id,
                    'name'          => $owner->name,
                    'service_name'  => $owner->service_name,
                    'email'         => $owner->email,
                    'total_buses'   => 0,
                    'total_trips'   => 0,
                    'total_bookings'=> 0,
                    'total_revenue' => 0,
                    'gross_income' => 0,
                    'owner_income' => 0,
                    'admin_service_income' => 0,
                    'admin_other_income' => 0,
                    'admin_profit' => 0,
                    'platform_fee'  => 0,
                    'net_earnings'  => 0,
                    'monthly_income' => [],
                    'weekly_income' => [],
                ];
            }

            $tripIds   = Trip::whereHas('schedule', fn($q) => $q->whereIn('bus_id', $busIds))->pluck('id');

            if ($tripIds->isEmpty()) {
                return [
                    'id'            => $owner->id,
                    'name'          => $owner->name,
                    'service_name'  => $owner->service_name,
                    'email'         => $owner->email,
                    'total_buses'   => $owner->buses->count(),
                    'total_trips'   => 0,
                    'total_bookings'=> 0,
                    'total_revenue' => 0,
                    'gross_income' => 0,
                    'owner_income' => 0,
                    'admin_service_income' => 0,
                    'admin_other_income' => 0,
                    'admin_profit' => 0,
                    'platform_fee'  => 0,
                    'net_earnings'  => 0,
                    'monthly_income' => [],
                    'weekly_income' => [],
                ];
            }

            $bookings  = Booking::whereIn('trip_id', $tripIds)->where('payment_status', 'paid');
            $totalBkgs = $bookings->count();
            $grossRev  = (float) $bookings->sum('total_price');
            $baseFare = $this->deriveBaseFare($grossRev);
            $ownerIncome = $this->calculateBusOwnerIncome($baseFare);
            $adminServiceIncome = $this->calculateAdminServiceIncome($baseFare);
            $adminOtherIncome = $this->calculateAdminOtherIncome($baseFare);
            $adminOwnerProfit = $adminServiceIncome + $adminOtherIncome;

            return [
                'id'            => $owner->id,
                'name'          => $owner->name,
                'service_name'  => $owner->service_name,
                'email'         => $owner->email,
                'total_buses'   => $owner->buses->count(),
                'total_trips'   => $tripIds->count(),
                'total_bookings'=> $totalBkgs,
                'total_revenue' => round($ownerIncome, 2),
                'gross_income' => round($grossRev, 2),
                'owner_income' => round($ownerIncome, 2),
                'admin_service_income' => round($adminServiceIncome, 2),
                'admin_other_income' => round($adminOtherIncome, 2),
                'admin_profit' => round($adminOwnerProfit, 2),
                'platform_fee'  => round($adminOwnerProfit, 2),
                'net_earnings'  => round($ownerIncome, 2),
                'monthly_income' => Booking::whereIn('trip_id', $tripIds)
                    ->where('payment_status', 'paid')
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period_key, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                    ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                    ->get()
                    ->map(function($item) {
                        $ownerMonthlyIncome = $this->calculateBusOwnerIncome($this->deriveBaseFare((float) $item->total));
                        $item->month = \Carbon\Carbon::createFromFormat('Y-m', $item->period_key)->format('M');
                        $item->total = round($ownerMonthlyIncome, 2);
                        unset($item->period_key);
                        return $item;
                    }),
                'weekly_income' => Booking::whereIn('trip_id', $tripIds)
                    ->where('payment_status', 'paid')
                    ->selectRaw("WEEK(created_at, 1) as week_num, CAST(SUM(total_price) AS DECIMAL(10,2)) as total, COUNT(*) as bookings")
                    ->where('created_at', '>=', now()->subWeeks(4))
                    ->groupByRaw("WEEK(created_at, 1)")
                    ->orderByRaw("WEEK(created_at, 1)")
                    ->get()
                    ->map(function($item, $index) {
                        $ownerWeeklyIncome = $this->calculateBusOwnerIncome($this->deriveBaseFare((float) $item->total));
                        $item->week = 'Week ' . ($index + 1);
                        $item->total = round($ownerWeeklyIncome, 2);
                        unset($item->week_num);
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
            'platform_revenue'   => round($adminProfit, 2),
            'bus_owner_revenue'  => $busOwnerRevenue,
            'admin_service_revenue' => round($adminServiceRevenue, 2),
            'admin_other_revenue' => round($adminOtherRevenue, 2),
            'gross_revenue' => round($grossRevenue, 2),
            'weekly_ledger' => $weeklyLedger,
            'balance_sheet' => [
                'gross_income' => round($grossRevenue, 2),
                'bus_owner_payout' => round($busOwnerRevenue, 2),
                'admin_service_income' => round($adminServiceRevenue, 2),
                'admin_other_income' => round($adminOtherRevenue, 2),
                'admin_profit' => round($adminProfit, 2),
            ],
            'monthly_income'     => $monthlyIncome,
            'weekly_income'      => $weeklyIncome,
            'bus_owners'         => $busOwners,
            'recent_bookings'    => $recentBookings,
        ]);
    }

    public function ledger(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|in:weekly,monthly',
            'month' => 'nullable|date_format:Y-m',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $period = $validated['period'] ?? 'weekly';
        $selectedMonth = $validated['month'] ?? null;

        if ($period === 'monthly' && $selectedMonth) {
            $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $monthDate->copy()->startOfMonth()->startOfDay();
            $endDate = $monthDate->copy()->endOfMonth()->endOfDay();
        } else {
            $startDate = isset($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->startOfDay()
                : null;
            $endDate = isset($validated['end_date'])
                ? Carbon::parse($validated['end_date'])->endOfDay()
                : null;
        }

        if (!$startDate || !$endDate) {
            if ($period === 'monthly') {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
            } else {
                $startDate = now()->startOfWeek(Carbon::MONDAY);
                $endDate = now()->endOfWeek(Carbon::SUNDAY);
            }
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $baseQuery = Payment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate]);

        if ($period === 'monthly') {
            $rows = $this->buildMonthlyLedgerRows($baseQuery);
        } else {
            $rows = $this->buildWeeklyLedgerRows($baseQuery);
        }

        $summary = [
            'total_bookings' => (int) $rows->sum('bookings'),
            'gross_amount' => round((float) $rows->sum('gross_amount'), 2),
            'owner_payout_amount' => round((float) $rows->sum('owner_payout_amount'), 2),
            'admin_service_amount' => round((float) $rows->sum('admin_service_amount'), 2),
            'admin_other_amount' => round((float) $rows->sum('admin_other_amount'), 2),
            'admin_profit_amount' => round((float) $rows->sum('admin_profit_amount'), 2),
        ];

        return response()->json([
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'rows' => $rows->values(),
            'summary' => $summary,
        ]);
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

    private function calculateAdminServiceIncome(float $baseFare): float
    {
        return $baseFare * (self::SERVICE_CHARGE_RATE / 2);
    }

    private function calculateAdminOtherIncome(float $baseFare): float
    {
        return $baseFare * self::OTHER_CHARGE_RATE;
    }

    private function buildWeeklyLedgerRows($baseQuery): Collection
    {
        $rowsByWeekday = (clone $baseQuery)
            ->selectRaw('DAYOFWEEK(paid_at) as weekday_idx')
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('CAST(SUM(gross_amount) AS DECIMAL(12,2)) as gross_amount')
            ->selectRaw('CAST(SUM(owner_payout_amount) AS DECIMAL(12,2)) as owner_payout_amount')
            ->selectRaw('CAST(SUM(admin_service_amount) AS DECIMAL(12,2)) as admin_service_amount')
            ->selectRaw('CAST(SUM(admin_other_amount) AS DECIMAL(12,2)) as admin_other_amount')
            ->selectRaw('CAST(SUM(admin_profit_amount) AS DECIMAL(12,2)) as admin_profit_amount')
            ->groupByRaw('DAYOFWEEK(paid_at)')
            ->get()
            ->keyBy('weekday_idx');

        return collect(range(1, 7))->map(function (int $isoDay) use ($rowsByWeekday) {
            $mysqlDay = $isoDay === 7 ? 1 : $isoDay + 1;
            $existing = $rowsByWeekday->get($mysqlDay);

            return [
                'label' => strtolower(Carbon::create()->startOfWeek(Carbon::MONDAY)->addDays($isoDay - 1)->format('l')),
                'bookings' => (int) ($existing->bookings ?? 0),
                'gross_amount' => round((float) ($existing->gross_amount ?? 0), 2),
                'owner_payout_amount' => round((float) ($existing->owner_payout_amount ?? 0), 2),
                'admin_service_amount' => round((float) ($existing->admin_service_amount ?? 0), 2),
                'admin_other_amount' => round((float) ($existing->admin_other_amount ?? 0), 2),
                'admin_profit_amount' => round((float) ($existing->admin_profit_amount ?? 0), 2),
            ];
        });
    }

    private function buildMonthlyLedgerRows($baseQuery): Collection
    {
        return (clone $baseQuery)
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as period_key")
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw('CAST(SUM(gross_amount) AS DECIMAL(12,2)) as gross_amount')
            ->selectRaw('CAST(SUM(owner_payout_amount) AS DECIMAL(12,2)) as owner_payout_amount')
            ->selectRaw('CAST(SUM(admin_service_amount) AS DECIMAL(12,2)) as admin_service_amount')
            ->selectRaw('CAST(SUM(admin_other_amount) AS DECIMAL(12,2)) as admin_other_amount')
            ->selectRaw('CAST(SUM(admin_profit_amount) AS DECIMAL(12,2)) as admin_profit_amount')
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->get()
            ->map(function ($row) {
                return [
                    'label' => Carbon::createFromFormat('Y-m', $row->period_key)->format('M Y'),
                    'bookings' => (int) $row->bookings,
                    'gross_amount' => round((float) $row->gross_amount, 2),
                    'owner_payout_amount' => round((float) $row->owner_payout_amount, 2),
                    'admin_service_amount' => round((float) $row->admin_service_amount, 2),
                    'admin_other_amount' => round((float) $row->admin_other_amount, 2),
                    'admin_profit_amount' => round((float) $row->admin_profit_amount, 2),
                ];
            });
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
            'service_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:8',
        ]);

        $busOwner = User::create([
            'name'         => $validated['name'],
            'service_name' => $validated['service_name'],
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
            'phone_number'     => 'required|string|max:20',
            'model'            => 'required|string',
            'total_seats'      => 'required|integer|min:1',
            'layout_type'      => 'required|in:1x2,2x2,1x3,2x3',
            'last_row_seats'   => 'required|integer|between:1,8',
        ]);

        if ((int) $validated['last_row_seats'] > (int) $validated['total_seats']) {
            return response()->json([
                'message' => 'Last row seats cannot exceed total seats.',
            ], 422);
        }

        // Verify the user is actually a bus owner
        $owner = User::find($validated['user_id']);
        if ($owner->role !== 'bus_owner') {
            return response()->json(['message' => 'Selected user is not a bus owner'], 400);
        }

        $bus = Bus::create([
            'user_id'          => $validated['user_id'],
            'bus_number_plate' => $validated['bus_number_plate'],
            'phone_number'     => $validated['phone_number'],
            'model'            => $validated['model'],
            'total_seats'      => $validated['total_seats'],
            'layout_type'      => $validated['layout_type'],
            'last_row_seats'   => $validated['last_row_seats'],
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
        $bus->update([
            'approval_status' => 'approved',
            'status' => 'active' // Activate bus when approved
        ]);

        return response()->json([
            'message' => 'Bus approved successfully',
            'bus' => $bus->load('busOwner')
        ]);
    }

    public function rejectBus($id)
    {
        $bus = Bus::findOrFail($id);
        $bus->update([
            'approval_status' => 'rejected',
            'status' => 'inactive' // Deactivate bus when rejected
        ]);

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
