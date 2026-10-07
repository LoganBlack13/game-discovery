<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_executable_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('executable_name');
            $table->string('path_fragment')->nullable();
            $table->string('launcher', 20)->nullable();
            $table->string('launcher_game_id')->nullable();
            $table->string('confidence', 20);
            $table->boolean('validated_by_user')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->index('executable_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_executable_mappings');
    }
};
