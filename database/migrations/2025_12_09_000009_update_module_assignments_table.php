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
        Schema::table('module_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('module_assignments', 'allow_late_submission')) {
                $table->boolean('allow_late_submission')->default(false)->after('due_date');
            }
            if (!Schema::hasColumn('module_assignments', 'late_penalty_percent')) {
                $table->decimal('late_penalty_percent', 5, 2)->default(0.00)->after('allow_late_submission');
            }
            if (!Schema::hasColumn('module_assignments', 'max_file_size_mb')) {
                $table->integer('max_file_size_mb')->default(10)->after('late_penalty_percent');
            }
            if (!Schema::hasColumn('module_assignments', 'allowed_file_types')) {
                $table->string('allowed_file_types')->nullable()->after('max_file_size_mb')->comment('Comma-separated: pdf,docx,zip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_assignments', function (Blueprint $table) {
            $table->dropColumn(['allow_late_submission', 'late_penalty_percent', 'max_file_size_mb', 'allowed_file_types']);
        });
    }
};
