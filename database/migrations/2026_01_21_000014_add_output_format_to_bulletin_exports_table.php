<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_exports', function (Blueprint $table) {
            $table->string('output_format')->default('docx')->after('filters');
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_exports', function (Blueprint $table) {
            $table->dropColumn('output_format');
        });
    }
};
