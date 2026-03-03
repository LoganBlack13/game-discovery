<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_request_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_request_id')->constrained('game_requests')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['game_request_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_request_votes');
    }
};
