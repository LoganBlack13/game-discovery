<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('companion_device_id')->nullable()->constrained('companion_devices')->nullOnDelete();
            $table->foreignId('game_executable_mapping_id')->nullable()->constrained('game_executable_mappings')->nullOnDelete();
            $table->string('source', 30);
            $table->string('detection_source', 30);
            $table->timestamp('started_at');
            $table->timestamp('last_heartbeat_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('active_seconds')->default(0);
            $table->unsignedInteger('idle_seconds')->default(0);
            $table->string('end_reason', 20)->nullable();
            $table->timestamp('effects_applied_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'game_id', 'started_at']);
            $table->index(['ended_at', 'last_heartbeat_at']);
            $table->index(['companion_device_id', 'game_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
