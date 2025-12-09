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
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'progress')) {
                $table->decimal('progress', 5, 2)->default(0.00)->after('enrolled_at')->comment('Progress percentage 0-100');
            }
            if (!Schema::hasColumn('enrollments', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('progress');
            }
            if (!Schema::hasColumn('enrollments', 'last_accessed_at')) {
                $table->dateTime('last_accessed_at')->nullable()->after('status');
            }
            
            // Update status enum if needed
            DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'paused', 'cancelled') DEFAULT 'active'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['progress', 'completed_at', 'last_accessed_at']);
        });
    }
};
