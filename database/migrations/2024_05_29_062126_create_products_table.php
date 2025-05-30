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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 100);
            $table->string('product_description', 200)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories');
//            $table->decimal('selling_price', 10, 2);
//            $table->decimal('cost_price', 10, 2);
            $table->string('barcode', 50)->unique()->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
