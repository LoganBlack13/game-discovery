<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->string('external_id')->nullable();
            $table->string('external_source')->nullable();
            $table->timestamps();

            $table->unique(['external_source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
