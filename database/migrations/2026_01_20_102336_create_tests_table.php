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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('test_title');
            $table->text('test_description');
            $table->text('instructions')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('time_limit')->nullable()->comment('Time limit in minutes');
            $table->integer('max_attempts')->default(1);
            $table->integer('total_marks');
            $table->integer('passing_marks')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'active', 'closed'])->default('draft');
            $table->enum('visibility_status', ['hidden', 'visible'])->default('hidden');
            $table->enum('grading_status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->boolean('results_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('module_id');
            $table->index('course_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
