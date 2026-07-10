<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Models\Bus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class TripController extends Controller
{
    private const ALLOWED_LOCATIONS = [
        'Kandy',
        'Pettah Bus Stand',
        'Kurunagala',
        'Matale',
        'Nuwaraeliya',
    ];

    private const LOCATION_ALIASES = [
        'Colombo' => 'Pettah Bus Stand',
        'Kurunegala' => 'Kurunagala',
        'Nuwara Eliya' => 'Nuwaraeliya',
    ];

    protected $tripRepo;

    public function __construct(TripRepositoryInterface $tripRepo)
    {
        $this->tripRepo = $tripRepo;
    }

    public function index(Request $request)
    {
        $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'date' => 'required|date',
        ]);

        $origin = $this->normalizeLocationName($request->origin);
        $destination = $this->normalizeLocationName($request->destination);

        if (!in_array($origin, self::ALLOWED_LOCATIONS, true) || !in_array($destination, self::ALLOWED_LOCATIONS, true)) {
            return response()->json([
                'message' => 'Invalid route selected.',
                'allowed_locations' => self::ALLOWED_LOCATIONS,
            ], 422);
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');

        $trips = $this->tripRepo->searchTrips($origin, $destination, $date);
        return response()->json($trips);
    }

    public function show($tripId)
    {
        $tripDetails = $this->tripRepo->getTripWithSeats($tripId);
        return response()->json($tripDetails);
    }

    public function createSchedule(Request $request)
    {
        $fields = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'departure_time' => 'required|date_format:H:i',
            'estimated_arrival_time' => 'required|date_format:H:i|after:departure_time',
            'price' => 'required|numeric|min:1',
        ]);

        $fields['origin'] = $this->normalizeLocationName($fields['origin']);
        $fields['destination'] = $this->normalizeLocationName($fields['destination']);

        if (!in_array($fields['origin'], self::ALLOWED_LOCATIONS, true) || !in_array($fields['destination'], self::ALLOWED_LOCATIONS, true)) {
            return response()->json([
                'message' => 'Invalid route selected.',
                'allowed_locations' => self::ALLOWED_LOCATIONS,
            ], 422);
        }

        $bus = Bus::findOrFail($fields['bus_id']);
        $authUserId = Auth::id();
        if (!$authUserId || $bus->user_id !== $authUserId) {
            return response()->json(['message' => 'Unauthorized for selected bus'], 403);
        }

        $schedule = $this->tripRepo->createSchedule($fields);
        return response()->json(['message' => 'Schedule created successfully', 'schedule' => $schedule], 201);
    }

    private function normalizeLocationName(string $location): string
    {
        $trimmed = trim($location);

        return self::LOCATION_ALIASES[$trimmed] ?? $trimmed;
    }
}
