<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('base_url', 500);
            $table->string('scraper_type', 100);
            $table->jsonb('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('health_score', 5, 2)->default(100.00);
            $table->integer('priority')->default(0);
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
