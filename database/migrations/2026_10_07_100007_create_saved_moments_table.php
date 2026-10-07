<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_moments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignUuid('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('companion_device_id')->nullable()->constrained('companion_devices')->nullOnDelete();
            $table->timestamp('captured_at');
            $table->string('disk', 20);
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size_bytes');
            $table->string('caption', 280)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'game_id', 'captured_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('companion_moment_hotkey', 40)->default('Ctrl+Shift+F9')->after('companion_suggest_unknown_games');
            $table->boolean('companion_moment_sound')->default(true)->after('companion_moment_hotkey');
            $table->boolean('companion_moment_upload')->default(true)->after('companion_moment_sound');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['companion_moment_hotkey', 'companion_moment_sound', 'companion_moment_upload']);
        });

        Schema::dropIfExists('saved_moments');
    }
};
