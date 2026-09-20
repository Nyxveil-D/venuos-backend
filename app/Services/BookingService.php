<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Support\Facades\DB;

final class BookingService
{
    /**
     * @param  array{court_id: int, booking_date: string, start_time: string, end_time: string}  $data
     */
    public function createBooking(array $data, int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId): Booking {
            $court = Court::where('id', $data['court_id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $conflict = Booking::where('court_id', $court->id)
                ->where('booking_date', $data['booking_date'])
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                abort(409, 'Slot waktu bentrok dengan booking lain.');
            }

            $hours = $this->calculateHours($data['start_time'], $data['end_time']);
            $totalPrice = bcmul((string) $court->hourly_rate, (string) $hours, 2);

            return Booking::create([
                'user_id' => $userId,
                'court_id' => $court->id,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'total_price' => $totalPrice,
                'status' => BookingStatus::Pending,
                'expires_at' => now()->addMinutes(15),
            ]);
        });
    }

    private function calculateHours(string $startTime, string $endTime): float
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        return ($end - $start) / 3600;
    }
}
