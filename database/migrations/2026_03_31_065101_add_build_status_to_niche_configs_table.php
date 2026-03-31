<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('niche_configs', function (Blueprint $table) {
            $table->string('build_status')->default('pending')->index()->after('is_active');
            $table->jsonb('build_metadata')->nullable()->after('build_status');
        });
    }

    public function down(): void
    {
        Schema::table('niche_configs', function (Blueprint $table) {
            $table->dropColumn(['build_status', 'build_metadata']);
        });
    }
};
