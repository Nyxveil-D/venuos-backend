<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Court $court;

    private User $user;

    private string $tomorrow;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->user = User::factory()->create();
        $this->tomorrow = CarbonImmutable::tomorrow()->format('Y-m-d');
    }

    public function test_booking_succeeds_on_empty_slot(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
        ]);
    }

    public function test_booking_calculates_correct_total_price(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(201);

        $this->assertEquals('200000.00', $response->json('data.total_price'));
    }

    public function test_booking_fails_on_conflicting_slot(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_price' => 200000,
            'status' => BookingStatus::Confirmed,
            'expires_at' => now()->addMinutes(15),
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->postJson('/api/v1/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_fails_on_exact_same_slot(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'total_price' => 100000,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addMinutes(15),
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->postJson('/api/v1/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        $response->assertStatus(409);
    }

    public function test_booking_succeeds_on_adjacent_non_overlapping_slot(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_price' => 200000,
            'status' => BookingStatus::Confirmed,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_booking_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(401);
    }
}
