<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tmdb_id')->nullable()->unique();
            $table->string('imdb_id', 20)->nullable()->index();
            $table->string('type', 50)->index();              // movie | series
            $table->string('title', 500);
            $table->string('original_title', 500)->nullable();
            $table->integer('year')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('poster_path', 500)->nullable();
            $table->string('backdrop_path', 500)->nullable();
            $table->jsonb('cast')->nullable();
            $table->string('director', 255)->nullable();
            $table->decimal('rating', 3, 1)->nullable()->index();
            $table->integer('vote_count')->nullable();
            $table->integer('runtime')->nullable();
            $table->string('trailer_url', 500)->nullable();
            $table->jsonb('alternative_titles')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('enrichment_status', 50)->default('pending')->index();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('watch_count')->default(0);
            $table->timestamps();
        });

        // GIN indexes for JSONB columns (PostgreSQL)
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX idx_contents_alt_titles ON contents USING GIN (alternative_titles)');
            DB::statement('CREATE INDEX idx_contents_cast ON contents USING GIN ("cast")');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
