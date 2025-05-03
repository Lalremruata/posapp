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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->integer('discount')->unsigned()->nullable();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('discount_type', ['automatic', 'manual', 'none'])->default('none');
            $table->decimal('unit_price', 10, 2)->comment('Price per unit after any unit discounts');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('Monetary value of discount');
            $table->decimal('line_total', 10, 2)->comment('Final price after all discounts');
            $table->decimal('subtotal', 10, 2);
            $table->dateTime('sale_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
