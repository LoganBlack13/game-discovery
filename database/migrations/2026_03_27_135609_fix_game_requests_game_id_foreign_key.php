<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_requests', function (Blueprint $table): void {
            $table->dropForeign(['game_id']);
            $table->foreign('game_id')->references('id')->on('games')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_requests', function (Blueprint $table): void {
            $table->dropForeign(['game_id']);
            $table->foreign('game_id')->references('id')->on('games');
        });
    }
};
