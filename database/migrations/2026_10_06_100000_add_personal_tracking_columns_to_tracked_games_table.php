<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracked_games', function (Blueprint $table): void {
            $table->string('platform')->nullable()->after('status');
            $table->string('progress')->nullable()->after('platform');
            $table->unsignedTinyInteger('progress_percent')->nullable()->after('progress');
            $table->text('notes')->nullable()->after('progress_percent');
            $table->timestamp('started_at')->nullable()->after('notes');
            $table->timestamp('last_activity_at')->nullable()->after('started_at');
            $table->timestamp('finished_at')->nullable()->after('last_activity_at');
            $table->string('priority')->nullable()->after('finished_at');
            $table->boolean('is_up_next')->default(false)->after('priority');
            $table->unsignedInteger('backlog_position')->nullable()->after('is_up_next');
            $table->unsignedTinyInteger('rating')->nullable()->after('backlog_position');
            $table->text('review')->nullable()->after('rating');
            $table->unsignedInteger('playtime_hours')->nullable()->after('review');
            $table->boolean('would_recommend')->nullable()->after('playtime_hours');

            $table->index(['user_id', 'status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tracked_games', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'status', 'last_activity_at']);
            $table->dropColumn([
                'platform',
                'progress',
                'progress_percent',
                'notes',
                'started_at',
                'last_activity_at',
                'finished_at',
                'priority',
                'is_up_next',
                'backlog_position',
                'rating',
                'review',
                'playtime_hours',
                'would_recommend',
            ]);
        });
    }
};
