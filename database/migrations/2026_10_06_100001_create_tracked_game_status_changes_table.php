<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_game_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracked_game_id')->constrained('tracked_games')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('reason')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tracked_game_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_game_status_changes');
    }
};
