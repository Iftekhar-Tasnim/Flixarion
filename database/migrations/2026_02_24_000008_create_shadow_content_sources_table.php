<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shadow_content_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('raw_filename', 2048);
            $table->string('file_path', 2048);
            $table->string('file_extension', 10);
            $table->bigInteger('file_size')->nullable();
            $table->string('detected_encoding', 50)->nullable();
            $table->jsonb('subtitle_paths')->nullable();
            $table->string('scan_batch_id', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->index('source_id');
            $table->index('scan_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shadow_content_sources');
    }
};
