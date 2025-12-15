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
        Schema::table('module_assignments', function (Blueprint $table) {
            // Only add columns that don't exist
            // submission_type, allowed_file_types, max_file_size_mb, allow_late_submission, late_penalty_percent already exist
            
            // Add max number of files
            $table->integer('max_files')->default(1)->after('max_file_size_mb')
                ->comment('Maximum number of files allowed');
            
            // Late submission deadline
            $table->dateTime('late_submission_deadline')->nullable()->after('late_penalty_percent')
                ->comment('Final deadline for late submissions');
            
            // Grading and feedback
            $table->boolean('require_rubric')->default(false)->after('late_submission_deadline')
                ->comment('Whether this assignment uses a grading rubric');
            $table->boolean('peer_review_enabled')->default(false)->after('require_rubric');
            $table->integer('peer_reviews_required')->nullable()->after('peer_review_enabled')
                ->comment('Number of peer reviews each student must complete');
            
            // Visibility and availability
            $table->dateTime('available_from')->nullable()->after('peer_reviews_required')
                ->comment('When assignment becomes available to students');
            $table->boolean('show_after_due_date')->default(true)->after('available_from')
                ->comment('Whether to show submissions/grades after due date');
            
            // Text submission settings
            $table->integer('min_words')->nullable()->after('show_after_due_date')
                ->comment('Minimum word count for text submissions');
            $table->integer('max_words')->nullable()->after('min_words')
                ->comment('Maximum word count for text submissions');
                
            // Submission instructions and grading notes
            $table->text('grading_criteria')->nullable()->after('max_words')
                ->comment('Specific grading criteria or rubric details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'max_files',
                'late_submission_deadline',
                'require_rubric',
                'peer_review_enabled',
                'peer_reviews_required',
                'available_from',
                'show_after_due_date',
                'min_words',
                'max_words',
                'grading_criteria',
            ]);
        });
    }
};
