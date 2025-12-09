<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Clean up database anomaly by removing duplicate old tables.
     * The new structure uses: course_modules -> module_quizzes, module_assignments, module_notes
     * The old structure used: modules -> quizzes (with questions/options), assignments (with submissions)
     */
    public function up(): void
    {
        // Drop dependent tables first
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
        
        // Drop old main tables
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('assignments');
        
        // Drop old modules table (replaced by course_modules)
        Schema::dropIfExists('modules');
        Schema::dropIfExists('lessons'); // Also related to old structure
    }

    /**
     * Reverse the migrations.
     * 
     * Recreate the old tables if rollback is needed
     */
    public function down(): void
    {
        // Recreate quizzes table
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Recreate assignments table
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->dateTime('deadline');
            $table->timestamps();
        });

        // Recreate questions table
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->text('question_text');
            $table->integer('points')->default(1);
            $table->timestamps();
        });

        // Recreate options table
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // Recreate submissions table
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('learner_id')->constrained('learners')->onDelete('cascade');
            $table->string('file_path');
            $table->text('feedback')->nullable();
            $table->integer('grade')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();
        });

        // Recreate quiz_attempts table
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->foreignId('learner_id')->constrained('learners')->onDelete('cascade');
            $table->integer('score')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }
};
