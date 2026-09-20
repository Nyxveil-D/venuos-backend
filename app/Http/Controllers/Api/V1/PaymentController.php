<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function pay(Booking $booking): JsonResponse
    {
        $payment = $this->paymentService->createPaymentIntent($booking);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function webhook(WebhookPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->handleWebhookNotification(
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed.',
            'data' => new PaymentResource($payment),
        ]);
    }
}
