<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->longText('html_body')->nullable()->after('description');
            $table->longText('text_body')->nullable()->after('html_body');
            $table->json('filters')->nullable()->after('text_body');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn(['html_body', 'text_body', 'filters']);
        });
    }
};
