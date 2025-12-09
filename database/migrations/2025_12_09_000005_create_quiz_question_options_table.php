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
        if (!Schema::hasTable('quiz_question_options')) {
            Schema::create('quiz_question_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
                $table->text('option_text');
                $table->boolean('is_correct')->default(false);
                $table->integer('order_index')->default(0);
                $table->timestamps();
                
                $table->index(['question_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_question_options');
    }
};
