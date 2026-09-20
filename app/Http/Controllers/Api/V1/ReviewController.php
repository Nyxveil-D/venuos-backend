<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Court;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function store(CreateReviewRequest $request, Booking $booking): JsonResponse
    {
        $review = $this->reviewService->createReview(
            $booking,
            (int) $request->user()->id,
            $request->validated(),
        );

        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function index(Court $court): JsonResponse
    {
        $reviews = $court->reviews()
            ->with('user')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Court reviews retrieved.',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }
}
