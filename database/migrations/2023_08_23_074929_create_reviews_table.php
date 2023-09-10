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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('expert_id');
            $table->unsignedBigInteger('work_id');

            $table->string('transaction_id')->nullable();

            $table->string('status')->default('checking');

            $table->text('message_failure')->nullable();
            $table->text('message_revision')->nullable();
            $table->text('message_review')->nullable();
            $table->text('message_notcounted')->nullable();

            $table->text('user_comment')->nullable();

            $table->string('link')->nullable();


            $table->foreign('work_id')->references('id')->on('works');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('expert_id')->references('id')->on('experts')->onDelete('cascade');;

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
