<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourtFloorType;
use App\Enums\CourtType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    protected $fillable = [
        'venue_id',
        'name',
        'court_type',
        'floor_type',
        'hourly_rate',
        'is_active',
        'thumbnail_url',
        'images',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'court_type' => CourtType::class,
            'floor_type' => CourtFloorType::class,
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'images' => 'array',
        ];
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
