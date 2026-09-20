<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredBookingsCommand extends Command
{
    protected $signature = 'app:cancel-expired-bookings';

    protected $description = 'Cancel bookings with status pending that have passed their expires_at deadline.';

    public function handle(): int
    {
        $count = DB::transaction(function (): int {
            return Booking::where('status', BookingStatus::Pending)
                ->where('expires_at', '<', now())
                ->update(['status' => BookingStatus::Cancelled]);
        });

        $this->info("Cancelled {$count} expired booking(s).");

        return self::SUCCESS;
    }
}
