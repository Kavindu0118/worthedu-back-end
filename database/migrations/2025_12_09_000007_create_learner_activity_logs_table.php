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
        if (!Schema::hasTable('learner_activity_logs')) {
            Schema::create('learner_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->date('activity_date');
                $table->decimal('hours_spent', 5, 2)->default(0.00);
                $table->integer('lessons_completed')->default(0);
                $table->integer('quizzes_taken')->default(0);
                $table->integer('assignments_submitted')->default(0);
                $table->timestamps();
                
                $table->unique(['user_id', 'activity_date'], 'unique_activity');
                $table->index(['user_id', 'activity_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_activity_logs');
    }
};
