<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If old column exists, rename and change type to MEDIUMBLOB
        if (Schema::hasColumn('instructors', 'cv_path')) {
            DB::statement('ALTER TABLE `instructors` CHANGE `cv_path` `cv` MEDIUMBLOB NOT NULL');
            return;
        }

        // If cv exists but not MEDIUMBLOB, modify its type
        if (Schema::hasColumn('instructors', 'cv')) {
            DB::statement('ALTER TABLE `instructors` MODIFY `cv` MEDIUMBLOB NOT NULL');
            return;
        }

        // Otherwise add the column as MEDIUMBLOB
        Schema::table('instructors', function (Blueprint $table) {
            $table->binary('cv')->nullable();
        });
        DB::statement('ALTER TABLE `instructors` MODIFY `cv` MEDIUMBLOB NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If cv exists, change it back to cv_path as VARCHAR(255)
        if (Schema::hasColumn('instructors', 'cv')) {
            DB::statement('ALTER TABLE `instructors` CHANGE `cv` `cv_path` VARCHAR(255) NOT NULL');
            return;
        }

        // If cv_path exists already, ensure it's string
        if (Schema::hasColumn('instructors', 'cv_path')) {
            DB::statement('ALTER TABLE `instructors` MODIFY `cv_path` VARCHAR(255) NOT NULL');
        }
    }
};
