<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CourtFloorType;
use App\Enums\CourtType;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Review;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $wifi = Amenity::create(['name' => 'WiFi', 'slug' => 'wifi']);
        $cafe = Amenity::create(['name' => 'Cafe', 'slug' => 'cafe']);
        $parking = Amenity::create(['name' => 'Parking', 'slug' => 'parking']);

        $venue1 = Venue::create([
            'name' => 'Champion Futsal',
            'address' => 'Jakarta Selatan',
            'open_time' => '08:00',
            'close_time' => '22:00',
        ]);
        $venue1->amenities()->attach([$wifi->id, $parking->id]);

        Court::create([
            'venue_id' => $venue1->id,
            'name' => 'Futsal 1',
            'court_type' => CourtType::Futsal,
            'floor_type' => CourtFloorType::Vinyl,
            'hourly_rate' => 120000,
        ]);

        $venue2 = Venue::create([
            'name' => 'Pro Badminton Arena',
            'address' => 'Jakarta Barat',
            'open_time' => '07:00',
            'close_time' => '23:00',
        ]);
        $venue2->amenities()->attach([$wifi->id, $cafe->id]);

        Court::create([
            'venue_id' => $venue2->id,
            'name' => 'Badminton A',
            'court_type' => CourtType::Badminton,
            'floor_type' => CourtFloorType::Wood,
            'hourly_rate' => 80000,
        ]);

        $venue3 = Venue::create([
            'name' => 'Elite Sports Complex',
            'address' => 'Tangerang',
            'open_time' => '06:00',
            'close_time' => '24:00',
        ]);
        $venue3->amenities()->attach([$wifi->id, $cafe->id, $parking->id]);

        Court::create([
            'venue_id' => $venue3->id,
            'name' => 'Basket Indoor',
            'court_type' => CourtType::Basket,
            'floor_type' => CourtFloorType::HardCourt,
            'hourly_rate' => 200000,
        ]);
        Court::create([
            'venue_id' => $venue3->id,
            'name' => 'Futsal Sintetis',
            'court_type' => CourtType::Futsal,
            'floor_type' => CourtFloorType::SyntheticGrass,
            'hourly_rate' => 150000,
        ]);

        // Add a mock review for average calculation
        $user = User::factory()->create();
        $booking = Booking::create([
            'user_id' => $user->id,
            'court_id' => $venue1->courts()->first()->id,
            'booking_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_price' => 120000,
            'status' => BookingStatus::Completed,
            'expires_at' => now(),
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'court_id' => $venue1->courts()->first()->id,
            'venue_id' => $venue1->id,
            'rating_field_condition' => 5,
            'rating_cleanliness' => 4,
            'rating_staff' => 5,
            'overall_rating' => 4.7,
        ]);
    }

    public function test_can_list_all_venues_with_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/venues');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        $data = $response->json('data');

        $championFutsal = collect($data)->firstWhere('name', 'Champion Futsal');

        $this->assertEquals(120000, $championFutsal['starting_from_price']);
        $this->assertEquals(['futsal'], $championFutsal['sports']);
        $this->assertEquals(4.7, $championFutsal['average_rating']);
        $this->assertCount(2, $championFutsal['amenities']);
    }

    public function test_can_filter_venues_by_sport(): void
    {
        $response = $this->getJson('/api/v1/venues?sport=futsal');

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // Champion Futsal and Elite Sports Complex

        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Champion Futsal', $names);
        $this->assertContains('Elite Sports Complex', $names);
    }

    public function test_can_filter_venues_by_floor_type(): void
    {
        $response = $this->getJson('/api/v1/venues?floor_type=wood');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pro Badminton Arena');
    }

    public function test_can_filter_venues_by_single_amenity(): void
    {
        $response = $this->getJson('/api/v1/venues?amenities=cafe');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Pro Badminton Arena', $names);
        $this->assertContains('Elite Sports Complex', $names);
    }

    public function test_can_filter_venues_by_multiple_amenities_an_d_condition(): void
    {
        // Must have both cafe AND parking
        $response = $this->getJson('/api/v1/venues?amenities=cafe,parking');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Elite Sports Complex');
    }

    public function test_can_search_venues_by_name_or_address(): void
    {
        $response = $this->getJson('/api/v1/venues?search=Barat');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pro Badminton Arena');

        $response2 = $this->getJson('/api/v1/venues?search=Elite');

        $response2->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Elite Sports Complex');
    }
}
