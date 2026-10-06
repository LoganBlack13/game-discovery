<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracked_game_id')->constrained('tracked_games')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_resume_goal')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('written_at');
            $table->timestamps();

            $table->index(['tracked_game_id', 'written_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
