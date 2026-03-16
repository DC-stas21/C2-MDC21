<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_calendar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel')->index();
            $table->string('title');
            $table->text('draft')->nullable();
            $table->string('status')->default('planned')->index();
            $table->string('asset')->nullable()->index();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_for')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_calendar');
    }
};
