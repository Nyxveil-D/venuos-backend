<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Venue;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Venue */
class VenueDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
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
            'thumbnail_url' => $this->thumbnail_url,
            'images' => $this->images,
            'ratings' => [
                'overall' => $this->reviews_avg_overall_rating ? round((float) $this->reviews_avg_overall_rating, 1) : null,
                'field_condition' => $this->reviews_avg_rating_field_condition ? round((float) $this->reviews_avg_rating_field_condition, 1) : null,
                'cleanliness' => $this->reviews_avg_rating_cleanliness ? round((float) $this->reviews_avg_rating_cleanliness, 1) : null,
                'staff' => $this->reviews_avg_rating_staff ? round((float) $this->reviews_avg_rating_staff, 1) : null,
            ],
            'amenities' => $this->whenLoaded('amenities', function () {
                return $this->amenities->map(fn ($amenity) => [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'slug' => $amenity->slug,
                    'icon' => $amenity->icon,
                ]);
            }),
            'courts' => $this->whenLoaded('courts', function () {
                return $this->courts->map(fn ($court) => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'court_type' => $court->court_type->value,
                    'floor_type' => $court->floor_type?->value,
                    'hourly_rate' => $court->hourly_rate,
                    'thumbnail_url' => $court->thumbnail_url,
                    'images' => $court->images,
                ]);
            }),
            'recent_reviews' => $this->whenLoaded('reviews', function () {
                return ReviewResource::collection($this->reviews);
            }),
        ];
    }
}
