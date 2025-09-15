<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verse_id');
            $table->string('language', 10)->index();
            $table->string('translator', 100)->index();
            $table->text('text');
            $table->timestamps();

            // Foreign key to verses
            $table->foreign('verse_id')
                ->references('id')
                ->on('verses')
                ->onDelete('cascade');

            // Composite unique index (verse_id + language + translator)
            $table->unique(
                ['verse_id', 'language', 'translator'],
                'translations_verse_lang_translator_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
