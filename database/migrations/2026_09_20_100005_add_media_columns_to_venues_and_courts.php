<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->string('thumbnail_url', 500)->nullable()->after('close_time');
            $table->json('images')->nullable()->after('thumbnail_url');
        });

        Schema::table('courts', function (Blueprint $table): void {
            $table->string('thumbnail_url', 500)->nullable()->after('is_active');
            $table->json('images')->nullable()->after('thumbnail_url');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropColumn(['thumbnail_url', 'images']);
        });

        Schema::table('courts', function (Blueprint $table): void {
            $table->dropColumn(['thumbnail_url', 'images']);
        });
    }
};
