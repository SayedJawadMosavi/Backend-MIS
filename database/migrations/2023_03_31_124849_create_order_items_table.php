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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->double('count')->unsigned();
            $table->double('weight')->unsigned();
            $table->double('price_per_killo')->unsigned();
            $table->foreignId('order_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete()->onUpdate('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->onUpdate('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
