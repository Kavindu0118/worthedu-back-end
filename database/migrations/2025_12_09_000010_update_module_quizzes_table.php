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
        Schema::table('module_quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('module_quizzes', 'passing_percentage')) {
                $table->decimal('passing_percentage', 5, 2)->default(70.00)->after('total_points');
            }
            if (!Schema::hasColumn('module_quizzes', 'max_attempts')) {
                $table->integer('max_attempts')->default(1)->after('passing_percentage')->comment('NULL or 0 for unlimited');
            }
            if (!Schema::hasColumn('module_quizzes', 'show_correct_answers')) {
                $table->boolean('show_correct_answers')->default(true)->after('max_attempts');
            }
            if (!Schema::hasColumn('module_quizzes', 'randomize_questions')) {
                $table->boolean('randomize_questions')->default(false)->after('show_correct_answers');
            }
            if (!Schema::hasColumn('module_quizzes', 'available_from')) {
                $table->dateTime('available_from')->nullable()->after('randomize_questions');
            }
            if (!Schema::hasColumn('module_quizzes', 'available_until')) {
                $table->dateTime('available_until')->nullable()->after('available_from');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_quizzes', function (Blueprint $table) {
            $table->dropColumn(['passing_percentage', 'max_attempts', 'show_correct_answers', 'randomize_questions', 'available_from', 'available_until']);
        });
    }
};
