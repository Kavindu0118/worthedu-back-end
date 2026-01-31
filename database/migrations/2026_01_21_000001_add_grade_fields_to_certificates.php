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
        Schema::table('certificates', function (Blueprint $table) {
            $table->decimal('quiz_weight', 5, 2)->default(0.15)->after('certificate_number');
            $table->decimal('assignment_weight', 5, 2)->default(0.25)->after('quiz_weight');
            $table->decimal('test_weight', 5, 2)->default(0.60)->after('assignment_weight');
            $table->decimal('final_grade', 5, 2)->nullable()->after('test_weight');
            $table->string('letter_grade', 2)->nullable()->after('final_grade');
            $table->enum('status', ['pass', 'fail'])->nullable()->after('letter_grade');
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->boolean('can_view')->default(false)->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'quiz_weight',
                'assignment_weight',
                'test_weight',
                'final_grade',
                'letter_grade',
                'status',
                'completed_at',
                'can_view'
            ]);
        });
    }
};
