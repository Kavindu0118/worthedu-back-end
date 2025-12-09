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
        if (!Schema::hasTable('assignment_submissions')) {
            Schema::create('assignment_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->constrained('module_assignments')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->longText('submission_text')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('file_name')->nullable();
                $table->integer('file_size_kb')->nullable();
                $table->dateTime('submitted_at')->useCurrent();
                $table->enum('status', ['submitted', 'graded', 'returned', 'resubmitted'])->default('submitted');
                $table->decimal('marks_obtained', 8, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->dateTime('graded_at')->nullable();
                $table->boolean('is_late')->default(false);
                $table->timestamps();
                
                $table->unique(['assignment_id', 'user_id'], 'unique_submission');
                $table->index(['status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
