<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CourtFloorType;
use App\Enums\CourtType;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVenueTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $customer;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->venue = Venue::create([
            'name' => 'Test Venue',
            'address' => 'Jl. Test',
            'open_time' => '08:00',
            'close_time' => '23:00',
        ]);
    }

    public function test_owner_can_create_court_with_floor_type(): void
    {
        $response = $this->actingAs($this->owner)->postJson("/api/v1/admin/venues/{$this->venue->id}/courts", [
            'name' => 'Court V1',
            'court_type' => CourtType::Futsal->value,
            'floor_type' => CourtFloorType::Vinyl->value,
            'hourly_rate' => 150000,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('courts', [
            'name' => 'Court V1',
            'floor_type' => 'vinyl',
        ]);
    }

    public function test_customer_cannot_create_court(): void
    {
        $response = $this->actingAs($this->customer)->postJson("/api/v1/admin/venues/{$this->venue->id}/courts", [
            'name' => 'Court V1',
            'court_type' => CourtType::Futsal->value,
            'hourly_rate' => 150000,
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_sync_amenities(): void
    {
        $am1 = Amenity::create(['name' => 'WiFi', 'slug' => 'wifi']);
        $am2 = Amenity::create(['name' => 'Cafe', 'slug' => 'cafe']);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/admin/venues/{$this->venue->id}/amenities", [
            'amenity_ids' => [$am1->id, $am2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('venue_amenity', [
            'venue_id' => $this->venue->id,
            'amenity_id' => $am1->id,
        ]);
    }
}
