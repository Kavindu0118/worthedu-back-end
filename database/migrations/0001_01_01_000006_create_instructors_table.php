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
        Schema::create('instructors', function (Blueprint $table) {
            // Primary key
            $table->id('instructor_id');

            // Link to users table (one-to-one)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Required personal details
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->text('address');
            $table->string('mobile_number');

            // Highest qualification enum
            $table->enum('highest_qualification', ['certificate', 'diploma', 'degree']);

            // Subject area and CV path (PDF) - store file path or filename
            $table->string('subject_area');
            $table->string('cv_path');

            // Optional note (text)
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
