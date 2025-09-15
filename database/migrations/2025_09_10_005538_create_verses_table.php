<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('surahs')->onDelete('cascade');
            $table->integer('verse_number');
            $table->text('text_arabic');
            $table->timestamps();

            $table->unique(['surah_id', 'verse_number']); // prevent duplicates
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verses');
    }
};
