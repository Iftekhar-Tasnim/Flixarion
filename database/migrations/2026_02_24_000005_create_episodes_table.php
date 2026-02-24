<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->integer('episode_number');
            $table->string('title', 255)->nullable();
            $table->bigInteger('tmdb_episode_id')->nullable();
            $table->text('overview')->nullable();
            $table->string('still_path', 500)->nullable();
            $table->integer('runtime')->nullable();
            $table->date('air_date')->nullable();
            $table->timestamps();

            $table->unique(['season_id', 'episode_number']);
            $table->index('content_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
