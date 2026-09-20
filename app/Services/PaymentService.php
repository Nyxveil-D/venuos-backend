<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PaymentService
{
    public function createPaymentIntent(Booking $booking): Payment
    {
        if ($booking->status !== BookingStatus::Pending) {
            abort(422, 'Only pending bookings can be paid.');
        }

        if ($booking->payment()->exists()) {
            abort(409, 'Payment already initiated for this booking.');
        }

        return Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_price,
            'status' => PaymentStatus::Pending,
            'payment_gateway_id' => 'SBX-'.Str::upper(Str::random(12)),
        ]);
    }

    /**
     * @param  array{order_id: string, transaction_status: string, payment_type?: string, transaction_id?: string}  $payload
     */
    public function handleWebhookNotification(array $payload): Payment
    {
        return DB::transaction(function () use ($payload): Payment {
            $payment = Payment::where('payment_gateway_id', $payload['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $booking = Booking::where('id', $payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            $txnStatus = $payload['transaction_status'];

            if (in_array($txnStatus, ['settlement', 'capture'], true)) {
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'payment_method' => $payload['payment_type'] ?? null,
                    'paid_at' => now(),
                ]);
                $booking->update(['status' => BookingStatus::Confirmed]);
            } elseif ($txnStatus === 'expire') {
                $payment->update(['status' => PaymentStatus::Expired]);
                $booking->update(['status' => BookingStatus::Cancelled]);
            } else {
                $payment->update(['status' => PaymentStatus::Failed]);
                $booking->update(['status' => BookingStatus::Cancelled]);
            }

            return $payment->refresh();
        });
    }
}
