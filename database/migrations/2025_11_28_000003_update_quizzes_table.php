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
        Schema::table('quizzes', function (Blueprint $table) {
            // Drop course_id foreign key and add module_id
            $table->dropForeign(['course_id']);
            $table->dropColumn(['course_id', 'title', 'description']);
            
            // Add new columns
            $table->foreignId('module_id')->after('id')->constrained('modules')->onDelete('cascade');
            $table->text('question')->after('module_id');
            $table->json('options')->after('question');
            $table->integer('correct_answer')->after('options'); // index of correct option
            $table->integer('points')->default(10)->after('correct_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn(['module_id', 'question', 'options', 'correct_answer', 'points']);
            
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
        });
    }
};
