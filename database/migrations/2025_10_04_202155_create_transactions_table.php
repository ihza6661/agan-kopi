<?php

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('note', 255)->nullable();
            $table->foreignId('suspended_from_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('change', 12, 2)->default(0);
            $table->enum('payment_method', [
                PaymentMethod::CASH->value,
                PaymentMethod::QRIS->value
            ])->default(PaymentMethod::CASH->value);
            $table->enum('status', [
                TransactionStatus::PENDING->value,
                TransactionStatus::SUSPENDED->value,
                TransactionStatus::PAID->value,
                TransactionStatus::CANCELED->value,
                TransactionStatus::REFUNDED->value
            ])->default(TransactionStatus::PENDING->value)
                ->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
