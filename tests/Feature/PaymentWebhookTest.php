<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Booking $booking;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $venue = Venue::create([
            'name' => 'Test Venue',
            'address' => 'Jl. Test',
            'open_time' => '08:00',
            'close_time' => '23:00',
        ]);

        $court = Court::create([
            'venue_id' => $venue->id,
            'name' => 'Court A',
            'court_type' => 'futsal',
            'hourly_rate' => 100000,
        ]);

        $user = User::factory()->create();
        $tomorrow = CarbonImmutable::tomorrow()->format('Y-m-d');

        $this->booking = Booking::create([
            'user_id' => $user->id,
            'court_id' => $court->id,
            'booking_date' => $tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_price' => 200000,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->payment = Payment::create([
            'booking_id' => $this->booking->id,
            'payment_gateway_id' => 'SBX-TESTWEBHOOK01',
            'amount' => 200000,
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function test_settlement_webhook_confirms_booking(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'order_id' => 'SBX-TESTWEBHOOK01',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_capture_webhook_confirms_booking(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'order_id' => 'SBX-TESTWEBHOOK01',
            'transaction_status' => 'capture',
            'payment_type' => 'credit_card',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_expire_webhook_cancels_booking(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'order_id' => 'SBX-TESTWEBHOOK01',
            'transaction_status' => 'expire',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'expired',
        ]);
    }

    public function test_deny_webhook_fails_payment_and_cancels_booking(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'order_id' => 'SBX-TESTWEBHOOK01',
            'transaction_status' => 'deny',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_webhook_with_invalid_order_id_returns_404(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'order_id' => 'NONEXISTENT',
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(404);
    }
}
