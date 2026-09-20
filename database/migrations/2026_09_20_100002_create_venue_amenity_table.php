<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_amenity', function (Blueprint $table): void {
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['venue_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_amenity');
    }
};
