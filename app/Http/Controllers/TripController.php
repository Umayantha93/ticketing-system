<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\TripRepositoryInterface;
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
            'date' => 'required|date|after_or_equal:today',
        ]);

        $trips = $this->tripRepo->searchTrips($request->origin, $request->destination, $request->date);
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
            'departure_time' => 'required|date_format:H:i',
            'estimated_arrival_time' => 'required|date_format:H:i|after:departure_time',
        ]);

        $schedule = $this->tripRepo->createSchedule($fields);
        return response()->json(['message' => 'Schedule created successfully', 'schedule' => $schedule], 201);
    }
}
