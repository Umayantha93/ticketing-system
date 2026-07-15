<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Models\Bus;
use App\Models\Destination;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class TripController extends Controller
{
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

        $originDestination = $this->resolveDestination($request->origin);
        $targetDestination = $this->resolveDestination($request->destination);

        $originCanonical = $originDestination?->name_en ?? trim((string) $request->origin);
        $destinationCanonical = $targetDestination?->name_en ?? trim((string) $request->destination);

        if (!$this->isKnownSearchLocation((string) $request->origin, $originCanonical) ||
            !$this->isKnownSearchLocation((string) $request->destination, $destinationCanonical)) {
            return response()->json([
                'message' => 'Invalid route selected.',
                'allowed_locations' => $this->getAvailableLocations(),
            ], 422);
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');

        $trips = $this->tripRepo->searchTrips($originCanonical, $destinationCanonical, $date);
        return response()->json($trips);
    }

    public function locations()
    {
        return response()->json([
            'locations' => $this->getAvailableLocations(),
        ]);
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
            'origin_destination_id' => 'required|exists:destinations,id',
            'destination_destination_id' => 'required|exists:destinations,id|different:origin_destination_id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'departure_time' => 'required|date_format:H:i',
            'estimated_arrival_time' => 'required|date_format:H:i|after:departure_time',
            'price' => 'required|numeric|min:1',
        ]);

        $originDestination = Destination::findOrFail((int) $fields['origin_destination_id']);
        $targetDestination = Destination::findOrFail((int) $fields['destination_destination_id']);
        $fields['origin'] = $originDestination->name_en;
        $fields['destination'] = $targetDestination->name_en;

        $bus = Bus::findOrFail($fields['bus_id']);
        $authUserId = Auth::id();
        if (!$authUserId || $bus->user_id !== $authUserId) {
            return response()->json(['message' => 'Unauthorized for selected bus'], 403);
        }

        $schedule = $this->tripRepo->createSchedule($fields);
        return response()->json(['message' => 'Schedule created successfully', 'schedule' => $schedule], 201);
    }

    private function getAvailableLocations(): array
    {
        return Destination::query()
            ->orderBy('name_en')
            ->get(['id', 'district_code', 'name_en', 'name_si', 'name_ta', 'aliases'])
            ->map(fn (Destination $destination) => [
                'id' => $destination->id,
                'district_code' => $destination->district_code,
                'name_en' => $destination->name_en,
                'name_si' => $destination->name_si,
                'name_ta' => $destination->name_ta,
                'aliases' => $destination->aliases ?? [],
            ])
            ->values()
            ->all();
    }

    private function resolveDestination(string $value): ?Destination
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return Destination::query()
            ->searchByAnyLanguage($trimmed)
            ->orderByRaw('LOWER(name_en) = ? DESC', [mb_strtolower($trimmed)])
            ->first();
    }

    private function isKnownSearchLocation(string $input, string $canonical): bool
    {
        if (Destination::query()->searchByAnyLanguage($input)->exists()) {
            return true;
        }

        return Schedule::query()
            ->whereHas('bus', fn ($query) => $query->approvedAndActive())
            ->where(function ($query) use ($canonical) {
                $query->where('origin', $canonical)->orWhere('destination', $canonical);
            })
            ->exists();
    }
}
