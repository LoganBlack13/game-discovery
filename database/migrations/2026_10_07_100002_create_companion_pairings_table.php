<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companion_pairings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('user_code', 8)->unique();
            $table->string('secret_hash', 64);
            $table->string('label', 100);
            $table->string('platform', 50);
            $table->string('app_version', 30)->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('companion_device_id')->nullable()->constrained('companion_devices')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companion_pairings');
    }
};
