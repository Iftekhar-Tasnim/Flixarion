<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('played_at')->useCurrent();

            $table->index(['user_id', 'played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};
