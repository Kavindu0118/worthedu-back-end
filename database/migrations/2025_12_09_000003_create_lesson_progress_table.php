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
        if (!Schema::hasTable('lesson_progress')) {
            Schema::create('lesson_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('lesson_id')->constrained('course_modules')->onDelete('cascade')->comment('References course_modules table');
                $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->integer('time_spent_minutes')->default(0);
                $table->string('last_position', 50)->nullable()->comment('Video timestamp or page number');
                $table->timestamps();
                
                $table->unique(['user_id', 'lesson_id'], 'unique_progress');
                $table->index(['user_id', 'lesson_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
