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
        Schema::table('modules', function (Blueprint $table) {
            // Add content, duration, and rename order_no to order
            $table->text('content')->nullable()->after('description');
            $table->integer('duration')->nullable()->after('content'); // duration in minutes
            $table->renameColumn('order_no', 'order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['content', 'duration']);
            $table->renameColumn('order', 'order_no');
        });
    }
};
