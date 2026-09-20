<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Court;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class AvailabilityService
{
    private const int SLOT_START_HOUR = 8;

    private const int SLOT_END_HOUR = 23;

    /**
     * @return array<int, array{hour: string, status: string}>
     */
    public function getSlots(Court $court, string $date): array
    {
        $venue = $court->venue;
        $openHour = (int) CarbonImmutable::parse($venue->open_time)->format('H');
        $closeHour = (int) CarbonImmutable::parse($venue->close_time)->format('H');

        $startHour = max(self::SLOT_START_HOUR, $openHour);
        $endHour = min(self::SLOT_END_HOUR, $closeHour);

        $bookedSlots = $this->getBookedSlots($court, $date);

        $slots = [];
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $slotStart = sprintf('%02d:00:00', $hour);
            $slotEnd = sprintf('%02d:00:00', $hour + 1);

            $isBooked = $bookedSlots->contains(
                fn (object $booking): bool => $booking->start_time < $slotEnd && $booking->end_time > $slotStart,
            );

            $slots[] = [
                'hour' => substr($slotStart, 0, 5).' - '.substr($slotEnd, 0, 5),
                'status' => $isBooked ? 'booked' : 'available',
            ];
        }

        return $slots;
    }

    /**
     * @return Collection<int, object>
     */
    private function getBookedSlots(Court $court, string $date): Collection
    {
        return $court->bookings()
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->select(['start_time', 'end_time'])
            ->get();
    }
}
