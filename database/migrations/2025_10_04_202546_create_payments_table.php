<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->enum('method', [PaymentMethod::CASH->value, PaymentMethod::QRIS->value])
                ->default(PaymentMethod::QRIS->value)
                ->index();
            $table->string('provider')->nullable()->index();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('provider_order_id')->nullable();
            $table->enum('status', [
                PaymentStatus::PENDING->value,
                PaymentStatus::SETTLEMENT->value,
                PaymentStatus::EXPIRE->value,
                PaymentStatus::CANCEL->value,
                PaymentStatus::DENY->value,
                PaymentStatus::FAILURE->value,
            ])->default(PaymentStatus::PENDING->value)->index();
            $table->decimal('amount', 12, 2);
            $table->text('qr_string')->nullable();
            $table->string('qr_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
