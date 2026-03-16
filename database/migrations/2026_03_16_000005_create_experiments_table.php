<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('asset')->nullable()->index();
            $table->jsonb('variants');
            $table->string('metric');
            $table->jsonb('results')->nullable();
            $table->string('winner')->nullable();
            $table->string('status')->default('running')->index();
            $table->boolean('confirmed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
