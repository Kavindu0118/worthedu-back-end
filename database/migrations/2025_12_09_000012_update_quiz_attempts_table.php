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
        // Create quiz_attempts table if it doesn't exist
        if (!Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('module_quizzes')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('attempt_number')->default(1);
                $table->dateTime('started_at')->useCurrent();
                $table->dateTime('completed_at')->nullable();
                $table->integer('time_taken_minutes')->nullable();
                $table->decimal('score', 5, 2)->nullable()->comment('Percentage score');
                $table->decimal('points_earned', 8, 2)->nullable();
                $table->decimal('total_points', 8, 2)->nullable();
                $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
                $table->boolean('passed')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'quiz_id']);
            });
        } else {
            // Update existing table
            Schema::table('quiz_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('quiz_attempts', 'user_id')) {
                    $table->foreignId('user_id')->after('quiz_id')->constrained('users')->onDelete('cascade');
                }
                if (!Schema::hasColumn('quiz_attempts', 'attempt_number')) {
                    $table->integer('attempt_number')->default(1)->after('user_id');
                }
                if (!Schema::hasColumn('quiz_attempts', 'started_at')) {
                    $table->dateTime('started_at')->useCurrent()->after('attempt_number');
                }
                if (!Schema::hasColumn('quiz_attempts', 'completed_at')) {
                    $table->dateTime('completed_at')->nullable()->after('started_at');
                }
                if (!Schema::hasColumn('quiz_attempts', 'time_taken_minutes')) {
                    $table->integer('time_taken_minutes')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('quiz_attempts', 'points_earned')) {
                    $table->decimal('points_earned', 8, 2)->nullable()->after('score');
                }
                if (!Schema::hasColumn('quiz_attempts', 'total_points')) {
                    $table->decimal('total_points', 8, 2)->nullable()->after('points_earned');
                }
                if (!Schema::hasColumn('quiz_attempts', 'status')) {
                    $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress')->after('total_points');
                }
                if (!Schema::hasColumn('quiz_attempts', 'passed')) {
                    $table->boolean('passed')->nullable()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
