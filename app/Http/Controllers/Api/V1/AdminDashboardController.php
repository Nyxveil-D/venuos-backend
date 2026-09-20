<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        if (! in_array($request->user()->role, [UserRole::Owner, UserRole::Staff], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Owner or staff role required.',
            ], 403);
        }

        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        $totalRevenueThisMonth = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereYear('booking_date', $now->year)
            ->whereMonth('booking_date', $now->month)
            ->sum('total_price');

        $totalBookingsToday = Booking::where('booking_date', $today)->count();

        $pendingBookingsCount = Booking::where('status', BookingStatus::Pending)->count();

        // Calculate utilization rate for today
        // 1. Get all active courts and sum their open hours
        $courts = Court::with('venue')->where('is_active', true)->get();
        $totalPossibleHours = 0.0;

        foreach ($courts as $court) {
            $venue = $court->venue;
            if ($venue) {
                $openStr = $venue->open_time instanceof CarbonInterface ? $venue->open_time->format('H:i') : substr((string) $venue->open_time, 11, 5);
                $closeStr = $venue->close_time instanceof CarbonInterface ? $venue->close_time->format('H:i') : substr((string) $venue->close_time, 11, 5);

                $openHour = (int) substr($openStr, 0, 2);
                $closeHour = (int) substr($closeStr, 0, 2);

                $totalPossibleHours += max(0, $closeHour - $openHour);
            }
        }

        // 2. Sum booked hours today
        $todayBookings = Booking::where('booking_date', $today)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->get();

        $bookedHours = 0.0;
        foreach ($todayBookings as $booking) {
            $startStr = $booking->start_time instanceof CarbonInterface ? $booking->start_time->format('H:i') : (string) $booking->start_time;
            $endStr = $booking->end_time instanceof CarbonInterface ? $booking->end_time->format('H:i') : (string) $booking->end_time;

            if (strlen($startStr) > 8) {
                $startStr = substr($startStr, 11, 5);
                $endStr = substr($endStr, 11, 5);
            }

            $startHour = (int) substr($startStr, 0, 2);
            $endHour = (int) substr($endStr, 0, 2);

            $bookedHours += max(0, $endHour - $startHour);
        }

        $utilizationRate = $totalPossibleHours > 0
            ? round(($bookedHours / $totalPossibleHours) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary retrieved.',
            'data' => [
                'total_revenue_this_month' => (float) $totalRevenueThisMonth,
                'total_bookings_today' => $totalBookingsToday,
                'pending_bookings_count' => $pendingBookingsCount,
                'court_utilization_rate' => $utilizationRate,
            ],
        ]);
    }
}
