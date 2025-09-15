<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            // Add translator column
            if (!Schema::hasColumn('translations', 'translator')) {
                $table->string('translator', 100)->after('language')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropColumn('translator');
        });
    }
};
