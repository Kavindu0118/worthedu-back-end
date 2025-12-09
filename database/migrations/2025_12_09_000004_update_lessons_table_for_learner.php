<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update course_modules table (this system uses course_modules as lessons)
        Schema::table('course_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('course_modules', 'type')) {
                $table->enum('type', ['video', 'reading', 'quiz', 'assignment'])->default('reading')->after('module_description');
            }
            if (!Schema::hasColumn('course_modules', 'content_url')) {
                $table->string('content_url', 500)->nullable()->after('type')->comment('Video URL, PDF URL, etc.');
            }
            if (!Schema::hasColumn('course_modules', 'content_text')) {
                $table->longText('content_text')->nullable()->after('content_url')->comment('Text content for reading lessons');
            }
            if (!Schema::hasColumn('course_modules', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('content_text');
            }
            if (!Schema::hasColumn('course_modules', 'is_mandatory')) {
                $table->boolean('is_mandatory')->default(true)->after('order_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn(['type', 'content_url', 'content_text', 'duration_minutes', 'is_mandatory']);
        });
    }
};
