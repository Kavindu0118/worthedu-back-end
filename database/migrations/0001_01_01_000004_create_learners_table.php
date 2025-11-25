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
        Schema::create('learners', function (Blueprint $table) {
            // Primary key named learner_id
            $table->id('learner_id');

            // Link to users table
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Personal details
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();

            // Highest educational qualification
            $table->enum('highest_qualification', ['none', 'certificate', 'diploma', 'degree'])->default('none');

            // Contact
            $table->string('mobile_number')->nullable();

            // Registration date (can be used instead/in addition to timestamps)
            $table->date('registration_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};
