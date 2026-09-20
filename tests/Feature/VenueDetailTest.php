<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_venue_detail_with_relations(): void
    {
        $venue = Venue::create([
            'name' => 'Champion Futsal',
            'address' => 'Jakarta Selatan',
            'open_time' => '08:00',
            'close_time' => '22:00',
        ]);

        $response = $this->getJson("/api/v1/venues/{$venue->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Champion Futsal')
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'address', 'open_time', 'close_time',
                    'thumbnail_url', 'images', 'ratings', 'amenities', 'courts', 'recent_reviews',
                ],
            ]);
    }
}
