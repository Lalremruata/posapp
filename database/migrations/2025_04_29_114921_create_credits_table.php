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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales');
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->string('payment_method');
            $table->string('transaction_number');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('balance', 10, 2); // Running balance
            $table->enum('status', ['active', 'partially_paid', 'paid'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
