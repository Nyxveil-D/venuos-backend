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

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Court $court;

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

        $this->tomorrow = CarbonImmutable::tomorrow()->format('Y-m-d');
    }

    public function test_availability_returns_all_slots(): void
    {
        $response = $this->getJson("/api/v1/courts/{$this->court->id}/availability?date={$this->tomorrow}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.court_id', $this->court->id);

        $slots = $response->json('data.slots');
        $this->assertCount(15, $slots);

        foreach ($slots as $slot) {
            $this->assertEquals('available', $slot['status']);
        }
    }

    public function test_booked_slots_show_as_booked(): void
    {
        $user = User::factory()->create();

        Booking::create([
            'user_id' => $user->id,
            'court_id' => $this->court->id,
            'booking_date' => $this->tomorrow,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'total_price' => 200000,
            'status' => BookingStatus::Confirmed,
            'expires_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/courts/{$this->court->id}/availability?date={$this->tomorrow}");

        $response->assertOk();

        $slots = collect($response->json('data.slots'));

        $booked = $slots->where('status', 'booked');
        $this->assertCount(2, $booked);

        $bookedHours = $booked->pluck('hour')->values()->all();
        $this->assertContains('10:00 - 11:00', $bookedHours);
        $this->assertContains('11:00 - 12:00', $bookedHours);

        $available = $slots->where('status', 'available');
        $this->assertCount(13, $available);
    }

    public function test_availability_requires_date_parameter(): void
    {
        $response = $this->getJson("/api/v1/courts/{$this->court->id}/availability");

        $response->assertStatus(422);
    }

    public function test_availability_rejects_past_date(): void
    {
        $yesterday = CarbonImmutable::yesterday()->format('Y-m-d');

        $response = $this->getJson("/api/v1/courts/{$this->court->id}/availability?date={$yesterday}");

        $response->assertStatus(422);
    }
}
