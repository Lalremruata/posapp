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
        Schema::create('sale_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('stock_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity')->unsigned();
            $table->integer('cost_price')->unsigned();
            $table->integer('selling_price')->unsigned();
            $table->integer('total_price')->unsigned();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('discount_source', ['manual', 'automatic'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_carts');
    }
};
