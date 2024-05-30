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
        Schema::create('entryreview_questions', function (Blueprint $table) {
            $table->id();

            $table->string('slug');
            $table->string('name');

            $table->json('questions');

            $table->integer('price')->default(100);

            $table->timestamps();
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entryreview_questions');
    }
};
