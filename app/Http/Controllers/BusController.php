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
            'bus_number_plate' => 'required|string|unique:buses,bus_number_plate',
            'model' => 'required|string',
            'total_seats' => 'required|integer|min:1',
            'layout_type' => 'required|in:2x2,2x1',
        ]);

        $busData = array_merge($validatedData, [
            'user_id' => auth()->id(),
            'status' => 'inactive', // Inactive until admin approves
            'approval_status' => 'pending', // Requires admin approval
            ]);

        $bus = $this->busRepo->create($busData);
        return response()->json(['message' => 'Bus registered successfully. Waiting for admin approval.', 'bus' => $bus], 201);
    }

    public function update(Request $request, $id)
    {
        $bus = $this->busRepo->findById($id);

        // Verify ownership
        if ($bus->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'bus_number_plate' => 'sometimes|string|unique:buses,bus_number_plate,' . $id,
            'model' => 'sometimes|string',
            'total_seats' => 'sometimes|integer|min:1',
            'layout_type' => 'sometimes|in:2x2,2x1',
            'status' => 'sometimes|in:active,inactive',
        ]);

        // If bus details are being changed (not just status), set to pending approval
        if (isset($validatedData['bus_number_plate']) || isset($validatedData['model']) ||
            isset($validatedData['total_seats']) || isset($validatedData['layout_type'])) {
            $validatedData['approval_status'] = 'pending';
        }

        $bus = $this->busRepo->update($id, $validatedData);
        return response()->json(['message' => 'Bus updated successfully. Changes pending admin approval.', 'bus' => $bus]);
    }
}
