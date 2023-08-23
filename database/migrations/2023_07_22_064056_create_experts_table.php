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
        Schema::create('experts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->string('avatar')->default('/images/avatar/default.jpg');
            $table->string('name');
            $table->string('about');
            $table->string('slug');
            $table->string('direction');
            $table->json('price')->nullable();

            $table->boolean('status')->default(false);
            $table->string('backtowork')->default("");

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experts');
    }
};
