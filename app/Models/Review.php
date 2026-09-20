<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'court_id',
        'venue_id',
        'rating_field_condition',
        'rating_cleanliness',
        'rating_staff',
        'overall_rating',
        'comment',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rating_field_condition' => 'integer',
            'rating_cleanliness' => 'integer',
            'rating_staff' => 'integer',
            'overall_rating' => 'decimal:1',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Court, $this> */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
