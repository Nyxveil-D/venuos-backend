<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenueDetailResource;
use App\Http\Resources\VenueResource;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Venue::query()
            ->with([
                'amenities',
                'courts' => fn ($q) => $q->where('is_active', true),
            ])
            ->withAvg('reviews', 'overall_rating');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sport')) {
            $query->whereHas('courts', function (Builder $q) use ($request): void {
                $q->where('court_type', $request->string('sport')->toString())
                    ->where('is_active', true);
            });
        }

        if ($request->filled('floor_type')) {
            $query->whereHas('courts', function (Builder $q) use ($request): void {
                $q->where('floor_type', $request->string('floor_type')->toString())
                    ->where('is_active', true);
            });
        }

        if ($request->filled('amenities')) {
            $amenities = explode(',', $request->string('amenities')->toString());
            foreach ($amenities as $amenity) {
                $query->whereHas('amenities', function (Builder $q) use ($amenity): void {
                    $q->where('slug', trim($amenity));
                });
            }
        }

        $perPage = (int) $request->query('per_page', 15);
        $venues = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Venues retrieved successfully.',
            'data' => VenueResource::collection($venues),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'per_page' => $venues->perPage(),
                'total' => $venues->total(),
                'last_page' => $venues->lastPage(),
            ],
        ]);
    }

    public function show(Venue $venue): JsonResponse
    {
        $venue->load([
            'amenities',
            'courts' => fn ($q) => $q->where('is_active', true),
            'reviews' => fn ($q) => $q->with('user')->latest()->take(5),
        ])->loadAvg('reviews', 'overall_rating')
            ->loadAvg('reviews', 'rating_field_condition')
            ->loadAvg('reviews', 'rating_cleanliness')
            ->loadAvg('reviews', 'rating_staff');

        return response()->json([
            'success' => true,
            'message' => 'Venue retrieved successfully.',
            'data' => new VenueDetailResource($venue),
        ]);
    }
}
