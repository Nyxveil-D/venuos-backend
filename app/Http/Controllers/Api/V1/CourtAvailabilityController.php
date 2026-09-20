<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckCourtAvailabilityRequest;
use App\Models\Court;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;

class CourtAvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {}

    public function __invoke(CheckCourtAvailabilityRequest $request, Court $court): JsonResponse
    {
        $slots = $this->availabilityService->getSlots(
            $court,
            $request->validated('date'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Availability retrieved.',
            'data' => [
                'court_id' => $court->id,
                'court_name' => $court->name,
                'date' => $request->validated('date'),
                'slots' => $slots,
            ],
        ]);
    }
}
