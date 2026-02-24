<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_genre', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['content_id', 'genre_id']);
            $table->index('genre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_genre');
    }
};
