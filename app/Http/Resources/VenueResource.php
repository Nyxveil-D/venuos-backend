<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Venue;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Venue */
class VenueResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $activeCourts = $this->whenLoaded('courts', fn () => $this->courts->where('is_active', true), collect());

        $sports = $activeCourts->map(fn ($court) => $court->court_type->value)->unique()->values();
        $minPrice = $activeCourts->min('hourly_rate');

        $openTime = $this->open_time instanceof CarbonInterface
            ? $this->open_time->format('H:i')
            : $this->open_time;

        $closeTime = $this->close_time instanceof CarbonInterface
            ? $this->close_time->format('H:i')
            : $this->close_time;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'sports' => $sports->isEmpty() ? null : $sports,
            'starting_from_price' => $minPrice ? (float) $minPrice : null,
            'average_rating' => $this->reviews_avg_overall_rating ? round((float) $this->reviews_avg_overall_rating, 1) : null,
            'amenities' => $this->whenLoaded('amenities', function () {
                return $this->amenities->map(fn ($amenity) => [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'slug' => $amenity->slug,
                    'icon' => $amenity->icon,
                ]);
            }),
        ];
    }
}
