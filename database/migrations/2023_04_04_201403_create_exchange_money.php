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
        Schema::create('exchange_money', function (Blueprint $table) {
            $table->id();
            $table->string("sender_name");
            $table->bigInteger('amount')->unsigned();
            $table->string("currency");
            $table->string("province");
            $table->string("phone_number")->nullable();
            $table->string("receiver_name");
            $table->string("receiver_father_name");
            $table->string("exchange_id");
            $table->bigInteger("receiver_id_no");
            $table->date("date")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_money');
    }
};
