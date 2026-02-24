<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shadow_content_sources', function (Blueprint $table) {
            $table->string('enrichment_status', 20)->default('pending')->after('scan_batch_id');
            $table->index('enrichment_status');
            $table->unique(['source_id', 'file_path'], 'shadow_source_file_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shadow_content_sources', function (Blueprint $table) {
            $table->dropUnique('shadow_source_file_unique');
            $table->dropColumn('enrichment_status');
        });
    }
};
