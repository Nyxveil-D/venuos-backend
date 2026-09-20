<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Booking $completedBooking;

    private Booking $pendingBooking;

    private Court $court;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create();

        $venue = Venue::create([
            'name' => 'Test Venue',
            'address' => 'Jl. Test',
            'open_time' => '08:00',
            'close_time' => '23:00',
        ]);

        $this->court = Court::create([
            'venue_id' => $venue->id,
            'name' => 'Court A',
            'court_type' => 'futsal',
            'hourly_rate' => 100000,
        ]);

        $this->completedBooking = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 200000,
            'status' => BookingStatus::Completed,
            'expires_at' => now(),
        ]);

        $this->pendingBooking = Booking::create([
            'user_id' => $this->customer->id,
            'court_id' => $this->court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 200000,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public function test_customer_can_review_completed_booking(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$this->completedBooking->id}/reviews", [
            'rating_field_condition' => 5,
            'rating_cleanliness' => 4,
            'rating_staff' => 5,
            'comment' => 'Great court!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Overall rating = (5 + 4 + 5) / 3 = 14 / 3 = 4.666... rounded to 4.7
        $this->assertDatabaseHas('reviews', [
            'booking_id' => $this->completedBooking->id,
            'user_id' => $this->customer->id,
            'overall_rating' => 4.7,
            'comment' => 'Great court!',
        ]);
    }

    public function test_customer_cannot_review_pending_booking(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$this->pendingBooking->id}/reviews", [
            'rating_field_condition' => 5,
            'rating_cleanliness' => 5,
            'rating_staff' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only completed bookings can be reviewed.');
    }

    public function test_customer_cannot_review_others_booking(): void
    {
        $otherCustomer = User::factory()->create();

        $response = $this->actingAs($otherCustomer)->postJson("/api/v1/bookings/{$this->completedBooking->id}/reviews", [
            'rating_field_condition' => 5,
            'rating_cleanliness' => 5,
            'rating_staff' => 5,
        ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_review_twice(): void
    {
        $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$this->completedBooking->id}/reviews", [
            'rating_field_condition' => 5,
            'rating_cleanliness' => 5,
            'rating_staff' => 5,
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$this->completedBooking->id}/reviews", [
            'rating_field_condition' => 1,
            'rating_cleanliness' => 1,
            'rating_staff' => 1,
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_public_can_view_court_reviews(): void
    {
        $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$this->completedBooking->id}/reviews", [
            'rating_field_condition' => 5,
            'rating_cleanliness' => 4,
            'rating_staff' => 5,
            'comment' => 'Great court!',
        ]);

        $response = $this->getJson("/api/v1/courts/{$this->court->id}/reviews");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.overall_rating', '4.7');
    }
}
