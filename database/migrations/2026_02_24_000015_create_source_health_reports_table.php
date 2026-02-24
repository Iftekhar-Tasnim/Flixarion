<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('source_health_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->string('isp_name', 100);
            $table->boolean('is_reachable');
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('reported_at')->useCurrent();

            $table->index(['source_id', 'isp_name'], 'idx_health_source_isp');
            $table->index('reported_at', 'idx_health_reported');

            // FK without cascade — source deletion handled separately
            $table->foreign('source_id')->references('id')->on('sources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_health_reports');
    }
};
