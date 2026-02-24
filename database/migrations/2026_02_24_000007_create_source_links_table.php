<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('source_links', function (Blueprint $table) {
            $table->id();
            $table->string('linkable_type', 50);  // 'content' or 'episode'
            $table->unsignedBigInteger('linkable_id');
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('file_path', 2048);
            $table->string('quality', 50)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('codec_info', 100)->nullable();
            $table->integer('part_number')->nullable();
            $table->jsonb('subtitle_paths')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id', 'status'], 'idx_source_links_poly');
            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_links');
    }
};
