<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing indexes that reference the old column types
        DB::statement('DROP INDEX IF EXISTS causer');
        DB::statement('DROP INDEX IF EXISTS subject');

        // Truncate any existing rows to avoid cast errors (no production data yet)
        DB::table('activity_log')->truncate();

        // Change bigint columns to uuid to match HasUuids models
        DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE uuid USING NULL');
        DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE uuid USING NULL');

        // Recreate indexes
        DB::statement('CREATE INDEX causer ON activity_log (causer_type, causer_id)');
        DB::statement('CREATE INDEX subject ON activity_log (subject_type, subject_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS causer');
        DB::statement('DROP INDEX IF EXISTS subject');

        DB::table('activity_log')->truncate();

        DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE bigint USING NULL');
        DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE bigint USING NULL');

        DB::statement('CREATE INDEX causer ON activity_log (causer_type, causer_id)');
        DB::statement('CREATE INDEX subject ON activity_log (subject_type, subject_id)');
    }
};
