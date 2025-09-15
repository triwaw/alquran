<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surahs', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique();
            $table->string('arabic_name');
            $table->string('english_name')->nullable(); // ✅ now nullable
            $table->string('urdu_name')->nullable();    // ✅ now nullable
            $table->string('revelation_type')->nullable();
            $table->integer('verses_count')->nullable();
            $table->integer('raku_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surahs');
    }
};
