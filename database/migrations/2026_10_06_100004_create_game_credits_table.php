<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_credits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role');
            $table->string('discipline');
            $table->timestamps();

            $table->unique(['game_id', 'person_id', 'role']);
        });

        Schema::table('games', function (Blueprint $table): void {
            $table->timestamp('credits_synced_at')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn('credits_synced_at');
        });

        Schema::dropIfExists('game_credits');
    }
};
