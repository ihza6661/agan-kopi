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
        Schema::table('transactions', function (Blueprint $table) {
            // Who confirmed the QRIS payment
            $table->foreignId('confirmed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            // When QRIS payment was manually confirmed
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            // Index for faster lookup of pending QRIS transactions
            $table->index(['status', 'payment_method', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_by', 'confirmed_at']);
            $table->dropIndex(['status', 'payment_method', 'created_at']);
        });
    }
};
