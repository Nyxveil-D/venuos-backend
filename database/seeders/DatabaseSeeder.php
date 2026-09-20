<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\CourtFloorType;
use App\Enums\CourtType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::create([
            'name' => 'Owner VenuOS',
            'email' => 'owner@venuos.com',
            'password' => 'password123',
            'phone_number' => '08110000001',
            'role' => UserRole::Owner,
        ]);

        User::create([
            'name' => 'Staff VenuOS',
            'email' => 'staff@venuos.com',
            'password' => 'password123',
            'phone_number' => '08110000002',
            'role' => UserRole::Staff,
        ]);

        $customer1 = User::create([
            'name' => 'Customer Satu',
            'email' => 'customer@gmail.com',
            'password' => 'password123',
            'phone_number' => '08120000001',
            'role' => UserRole::Customer,
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'password' => 'password123',
            'phone_number' => '08120000002',
            'role' => UserRole::Customer,
        ]);

        $venue = Venue::create([
            'name' => 'VenuOS Arena Grand Mall',
            'address' => 'Jl. Grand Mall No. 1, Jakarta Selatan',
            'open_time' => '08:00',
            'close_time' => '23:00',
            'thumbnail_url' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=600&auto=format&fit=crop',
            'images' => [
                'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=1200&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1518658760085-30752538cbac?q=80&w=1200&auto=format&fit=crop',
            ],
        ]);

        $amenities = [
            ['name' => 'Cafe & Resto', 'slug' => 'cafe-resto', 'icon' => 'coffee'],
            ['name' => 'Musholla', 'slug' => 'musholla', 'icon' => 'mosque'],
            ['name' => 'Parkir Mobil', 'slug' => 'parkir-mobil', 'icon' => 'car'],
            ['name' => 'Parkir Motor', 'slug' => 'parkir-motor', 'icon' => 'motorcycle'],
            ['name' => 'Wi-Fi', 'slug' => 'wifi', 'icon' => 'wifi'],
            ['name' => 'Toilet / Kamar Mandi', 'slug' => 'toilet', 'icon' => 'bath'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::create($amenity);
        }

        $venue->amenities()->attach(Amenity::pluck('id'));

        $futsalCourt = Court::create([
            'venue_id' => $venue->id,
            'name' => 'Lapangan Futsal Vinyl A',
            'court_type' => CourtType::Futsal,
            'floor_type' => CourtFloorType::Vinyl,
            'hourly_rate' => 150000.00,
            'thumbnail_url' => 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?q=80&w=600&auto=format&fit=crop',
            'images' => ['https://images.unsplash.com/photo-1526232761682-d26e03ac148e?q=80&w=1200&auto=format&fit=crop'],
        ]);

        Court::create([
            'venue_id' => $venue->id,
            'name' => 'Lapangan Badminton Pro 1',
            'court_type' => CourtType::Badminton,
            'floor_type' => CourtFloorType::Wood,
            'hourly_rate' => 60000.00,
            'thumbnail_url' => 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?q=80&w=600&auto=format&fit=crop',
            'images' => ['https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?q=80&w=1200&auto=format&fit=crop'],
        ]);

        Court::create([
            'venue_id' => $venue->id,
            'name' => 'Lapangan Basket Indoor',
            'court_type' => CourtType::Basket,
            'floor_type' => CourtFloorType::HardCourt,
            'hourly_rate' => 200000.00,
            'thumbnail_url' => 'https://images.unsplash.com/photo-1505666287802-931dc83948e9?q=80&w=600&auto=format&fit=crop',
            'images' => ['https://images.unsplash.com/photo-1505666287802-931dc83948e9?q=80&w=1200&auto=format&fit=crop'],
        ]);

        $tomorrow = CarbonImmutable::tomorrow()->format('Y-m-d');

        $confirmedBooking = Booking::create([
            'user_id' => $customer1->id,
            'court_id' => $futsalCourt->id,
            'booking_date' => $tomorrow,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'total_price' => 300000.00,
            'status' => BookingStatus::Confirmed,
            'expires_at' => now(),
        ]);

        Payment::create([
            'booking_id' => $confirmedBooking->id,
            'payment_gateway_id' => 'SBX-SEED-CONFIRMED',
            'payment_method' => 'bank_transfer',
            'amount' => 300000.00,
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        Booking::create([
            'user_id' => $customer1->id,
            'court_id' => $futsalCourt->id,
            'booking_date' => $tomorrow,
            'start_time' => '15:00',
            'end_time' => '16:00',
            'total_price' => 150000.00,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addMinutes(15),
        ]);
    }
}
