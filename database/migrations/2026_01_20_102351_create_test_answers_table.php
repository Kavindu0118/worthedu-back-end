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
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('test_submissions')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('test_questions')->onDelete('cascade');
            $table->enum('question_type', ['mcq', 'descriptive', 'file_upload']);
            $table->string('selected_option', 500)->nullable()->comment('For MCQ');
            $table->text('text_answer')->nullable()->comment('For descriptive');
            $table->string('file_url', 500)->nullable()->comment('For file upload');
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->decimal('points_awarded', 10, 2)->nullable();
            $table->integer('max_points');
            $table->boolean('is_correct')->nullable()->comment('For auto-gradable questions');
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->index('submission_id');
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_answers');
    }
};
