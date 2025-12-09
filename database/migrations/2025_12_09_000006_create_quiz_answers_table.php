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
        if (!Schema::hasTable('quiz_answers')) {
            Schema::create('quiz_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('quiz_attempts')->onDelete('cascade');
                $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
                $table->json('selected_option_ids')->nullable()->comment('Array of option IDs');
                $table->boolean('is_correct')->nullable();
                $table->decimal('points_earned', 5, 2)->default(0.00);
                $table->dateTime('answered_at')->useCurrent();
                $table->timestamps();
                
                $table->unique(['attempt_id', 'question_id'], 'unique_answer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
