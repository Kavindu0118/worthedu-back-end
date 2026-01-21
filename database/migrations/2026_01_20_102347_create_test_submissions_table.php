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
        Schema::create('test_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('submitted_at')->nullable();
            $table->enum('submission_status', ['in_progress', 'submitted', 'late', 'not_submitted'])->default('in_progress');
            $table->integer('attempt_number')->default(1);
            $table->dateTime('started_at');
            $table->integer('time_taken')->nullable()->comment('Time taken in minutes');
            $table->decimal('total_score', 10, 2)->nullable();
            $table->enum('grading_status', ['pending', 'graded', 'published'])->default('pending');
            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('instructor_feedback')->nullable();
            $table->timestamps();
            
            $table->index('test_id');
            $table->index('student_id');
            $table->index('grading_status');
            $table->unique(['test_id', 'student_id', 'attempt_number'], 'unique_test_student_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_submissions');
    }
};
