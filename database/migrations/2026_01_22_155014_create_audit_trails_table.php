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
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('entity_type', 50);      // 'transaction', 'product', etc.
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 50);           // 'created', 'paid', 'canceled', 'stock_deducted'
            $table->json('before')->nullable();     // Snapshot before change
            $table->json('after')->nullable();      // Snapshot after change
            $table->json('metadata')->nullable();   // Additional context
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for querying
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
