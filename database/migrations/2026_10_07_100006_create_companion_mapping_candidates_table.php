<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companion_mapping_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('companion_device_id')->nullable()->constrained('companion_devices')->nullOnDelete();
            $table->string('fingerprint', 64);
            $table->string('executable_name');
            $table->string('path_fragment')->nullable();
            $table->string('launcher', 20)->nullable();
            $table->string('launcher_game_id')->nullable();
            $table->string('display_name')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('total_seconds')->default(0);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->string('status', 20)->default('pending');
            $table->foreignId('proposed_game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->string('proposal_confidence', 20)->nullable();
            $table->unsignedBigInteger('igdb_game_id')->nullable();
            $table->foreignId('game_executable_mapping_id')->nullable()->constrained('game_executable_mappings')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'fingerprint']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('companion_suggest_unknown_games')->default(true)->after('companion_tracking_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('companion_suggest_unknown_games');
        });

        Schema::dropIfExists('companion_mapping_candidates');
    }
};
