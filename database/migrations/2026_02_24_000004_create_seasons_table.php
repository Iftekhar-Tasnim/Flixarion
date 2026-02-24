<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->integer('season_number');
            $table->bigInteger('tmdb_season_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('poster_path', 500)->nullable();
            $table->text('overview')->nullable();
            $table->integer('episode_count')->nullable();
            $table->date('air_date')->nullable();
            $table->timestamps();

            $table->unique(['content_id', 'season_number']);
            $table->index('content_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
