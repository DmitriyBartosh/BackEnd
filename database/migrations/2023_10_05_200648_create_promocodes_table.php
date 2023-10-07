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
        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('name');
            $table->string('direction');
            $table->unsignedInteger('periodicity');
            $table->date('expired_at');
            $table->string('code');

            $table->string('status')->default('active');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes');
    }
};
