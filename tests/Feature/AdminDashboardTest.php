<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_dashboard_summary(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner]);

        $venue = Venue::create([
            'name' => 'Test Venue',
            'address' => 'Test Address',
            'open_time' => '08:00',
            'close_time' => '22:00', // 14 hours total
        ]);

        $court = Court::create([
            'venue_id' => $venue->id,
            'name' => 'Court 1',
            'court_type' => 'futsal',
            'hourly_rate' => 100000,
            'is_active' => true,
        ]);

        $customer = User::factory()->create(['role' => UserRole::Customer]);

        // Booking today (2 hours)
        Booking::create([
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'total_price' => 200000,
            'status' => BookingStatus::Confirmed,
            'expires_at' => now(),
        ]);

        // Pending booking
        Booking::create([
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_price' => 100000,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($owner)->getJson('/api/v1/admin/dashboard/summary');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_revenue_this_month', 200000)
            ->assertJsonPath('data.total_bookings_today', 1)
            ->assertJsonPath('data.pending_bookings_count', 1);

        // Utilization: 2 hours booked out of 14 hours possible = ~14.29%
        $this->assertEquals(14.29, $response->json('data.court_utilization_rate'));
    }
}
