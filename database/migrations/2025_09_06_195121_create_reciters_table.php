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
    if (!Schema::hasTable('reciters')) {
        Schema::create('reciters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('style')->nullable();
            $table->string('country')->nullable();
            $table->string('language')->nullable();
            $table->timestamps();
        });
    }
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reciters');
    }
};
