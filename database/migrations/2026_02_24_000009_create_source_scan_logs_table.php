<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('source_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 20);           // 'collector' or 'enricher'
            $table->string('status', 50);           // 'started', 'completed', 'failed'
            $table->integer('items_found')->default(0);
            $table->integer('items_matched')->default(0);
            $table->integer('items_failed')->default(0);
            $table->text('error_log')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            $table->index('source_id');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_scan_logs');
    }
};
