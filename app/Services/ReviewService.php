<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;

final class ReviewService
{
    /**
     * @param  array{rating_field_condition: int, rating_cleanliness: int, rating_staff: int, comment?: string|null}  $data
     */
    public function createReview(Booking $booking, int $userId, array $data): Review
    {
        if ($booking->status !== BookingStatus::Completed) {
            abort(422, 'Only completed bookings can be reviewed.');
        }

        if ($booking->user_id !== $userId) {
            abort(403, 'You can only review your own bookings.');
        }

        if ($booking->review()->exists()) {
            abort(409, 'This booking has already been reviewed.');
        }

        $overall = round(
            ($data['rating_field_condition'] + $data['rating_cleanliness'] + $data['rating_staff']) / 3,
            1,
        );

        return Review::create([
            'booking_id' => $booking->id,
            'user_id' => $userId,
            'court_id' => $booking->court_id,
            'venue_id' => $booking->court->venue_id,
            'rating_field_condition' => $data['rating_field_condition'],
            'rating_cleanliness' => $data['rating_cleanliness'],
            'rating_staff' => $data['rating_staff'],
            'overall_rating' => $overall,
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
