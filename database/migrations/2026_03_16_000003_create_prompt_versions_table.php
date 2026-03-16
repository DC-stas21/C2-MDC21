<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('agent_type')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('model');
            $table->text('prompt_text');
            $table->jsonb('metrics')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();

            $table->unique(['agent_type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_versions');
    }
};
