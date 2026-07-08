<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Models\Bus;
use Carbon\Carbon;
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
            'origin' => 'required|in:Colombo,Kandy',
            'destination' => 'required|in:Colombo,Kandy',
            'date' => 'required|date',
        ]);

        $origin = ucfirst(strtolower($request->origin));
        $destination = ucfirst(strtolower($request->destination));
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
            'origin' => 'required|in:Colombo,Kandy',
            'destination' => 'required|in:Colombo,Kandy',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'departure_time' => 'required|date_format:H:i',
            'estimated_arrival_time' => 'required|date_format:H:i|after:departure_time',
            'price' => 'required|numeric|min:1',
        ]);

        $bus = Bus::findOrFail($fields['bus_id']);
        if ($bus->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized for selected bus'], 403);
        }

        $schedule = $this->tripRepo->createSchedule($fields);
        return response()->json(['message' => 'Schedule created successfully', 'schedule' => $schedule], 201);
    }
}
