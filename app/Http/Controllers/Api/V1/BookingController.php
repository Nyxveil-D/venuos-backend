<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking(
            $request->validated(),
            (int) $request->user()->id,
        );

        $booking->load('court');

        return response()->json([
            'success' => true,
            'message' => 'Booking created. Complete payment within 15 minutes.',
            'data' => new BookingResource($booking),
        ], 201);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with('court')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'User bookings retrieved.',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }
}
