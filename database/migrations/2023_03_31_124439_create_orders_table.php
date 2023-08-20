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
        Schema::create('orders', function (Blueprint $table) {

            $table->id();
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete()->onUpdate('cascade');
            $table->string('customer_name');
            $table->unsignedBigInteger('group_number');
            $table->string('father_name');
            $table->string('grand_father_name');
            $table->string('tazkira_id')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('delivary_type')->nullable();
            $table->text('description')->nullable();
            $table->double('price_per_killo')->unsigned();
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
        Schema::dropIfExists('orders');
    }
};
