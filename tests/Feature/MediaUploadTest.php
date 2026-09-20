<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_thumbnail(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create(['role' => UserRole::Owner]);

        $venue = Venue::create([
            'name' => 'Champion Futsal',
            'address' => 'Jakarta Selatan',
            'open_time' => '08:00',
            'close_time' => '22:00',
        ]);

        $file = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->actingAs($owner)->postJson("/api/v1/admin/venues/{$venue->id}/upload-image", [
            'image' => $file,
            'type' => 'thumbnail',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $venue->refresh();
        $this->assertNotNull($venue->thumbnail_url);
    }
}
