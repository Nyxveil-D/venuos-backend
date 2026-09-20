<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, [UserRole::Staff, UserRole::Owner], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Staff or owner role required.',
            ], 403);
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed bookings can be checked in.',
            ], 422);
        }

        $booking->update(['status' => BookingStatus::Completed]);

        $booking->load('court');

        return response()->json([
            'success' => true,
            'message' => 'Booking checked in.',
            'data' => new BookingResource($booking),
        ]);
    }
}
