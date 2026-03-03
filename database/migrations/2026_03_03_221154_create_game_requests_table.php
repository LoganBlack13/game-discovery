<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('normalized_title')->unique();
            $table->string('display_title');
            $table->unsignedInteger('request_count')->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('game_id')->nullable()->constrained('games');
            $table->timestamp('added_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_requests');
    }
};
