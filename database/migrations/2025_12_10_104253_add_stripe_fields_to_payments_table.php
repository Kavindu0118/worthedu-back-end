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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_payment_method_id')->nullable()->after('payment_intent_id');
            $table->string('stripe_customer_id')->nullable()->after('stripe_payment_method_id');
            $table->json('metadata')->nullable()->after('receipt_url');
            $table->text('error_message')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_method_id', 'stripe_customer_id', 'metadata', 'error_message']);
        });
    }
};
