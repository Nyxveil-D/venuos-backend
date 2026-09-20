<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCourtRequest;
use App\Http\Requests\SyncVenueAmenitiesRequest;
use App\Http\Requests\UpdateCourtRequest;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminVenueController extends Controller
{
    public function storeCourt(Request $request, Venue $venue, CreateCourtRequest $courtRequest): JsonResponse
    {
        if (! in_array($request->user()->role, [UserRole::Owner, UserRole::Staff], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Owner or staff role required.',
            ], 403);
        }

        $court = $venue->courts()->create($courtRequest->validated());

        return response()->json([
            'success' => true,
            'message' => 'Court created.',
            'data' => $court,
        ], 201);
    }

    public function updateCourt(Request $request, Court $court, UpdateCourtRequest $courtRequest): JsonResponse
    {
        if (! in_array($request->user()->role, [UserRole::Owner, UserRole::Staff], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Owner or staff role required.',
            ], 403);
        }

        $court->update($courtRequest->validated());

        return response()->json([
            'success' => true,
            'message' => 'Court updated.',
            'data' => $court->fresh(),
        ]);
    }

    public function syncAmenities(Request $request, Venue $venue, SyncVenueAmenitiesRequest $amenityRequest): JsonResponse
    {
        if (! in_array($request->user()->role, [UserRole::Owner, UserRole::Staff], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Owner or staff role required.',
            ], 403);
        }

        $venue->amenities()->sync($amenityRequest->validated('amenity_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Venue amenities synced.',
            'data' => $venue->load('amenities'),
        ]);
    }

    public function uploadImage(Request $request, Venue $venue): JsonResponse
    {
        if (! in_array($request->user()->role, [UserRole::Owner, UserRole::Staff], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Owner or staff role required.',
            ], 403);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'type' => ['required', 'string', Rule::in(['thumbnail', 'gallery'])],
        ]);

        $path = $request->file('image')->store('venues', 'public');
        $url = Storage::disk('public')->url($path);

        if ($request->input('type') === 'thumbnail') {
            $venue->update(['thumbnail_url' => $url]);
        } else {
            $images = $venue->images ?? [];
            $images[] = $url;
            $venue->update(['images' => $images]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'data' => [
                'url' => $url,
                'type' => $request->input('type'),
            ],
        ]);
    }
}
