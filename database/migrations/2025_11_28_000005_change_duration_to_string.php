<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change duration column type from integer to string in courses table
        DB::statement('ALTER TABLE `courses` MODIFY `duration` VARCHAR(50) NULL');
        
        // Change duration column type from integer to string in modules table  
        DB::statement('ALTER TABLE `modules` MODIFY `duration` VARCHAR(50) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `courses` MODIFY `duration` INT NULL');
        DB::statement('ALTER TABLE `modules` MODIFY `duration` INT NULL');
    }
};
