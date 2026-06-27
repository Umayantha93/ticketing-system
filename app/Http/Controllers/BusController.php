<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\BusRepositoryInterface;

class BusController extends Controller
{
    protected $busRepo;

    public function __construct(BusRepositoryInterface $busRepo)
    {
        $this->busRepo = $busRepo;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'bus_number' => 'required|string|unique:buses,bus_number',
            'model' => 'required|string',
            'total_seats' => 'required|integer|min:1',
            'layout_type' => 'required|in:2x2,2x1',
        ]);

        $busData = array_merge($validatedData, [
            'user_id' => auth()->id(),
            'status' => 'approved',
            ]);

        $bus = $this->busRepo->create($busData);
        return response()->json(['message' => 'Bus created successfully', 'bus' => $bus], 201);
    }
}