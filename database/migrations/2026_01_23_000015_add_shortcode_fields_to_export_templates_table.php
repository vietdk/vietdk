<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('export_templates', 'template_type')) {
                $table->string('template_type')->default('simple')->after('description');
            }
            if (!Schema::hasColumn('export_templates', 'shortcode_body')) {
                $table->longText('shortcode_body')->nullable()->after('text_body');
            }
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            if (Schema::hasColumn('export_templates', 'shortcode_body')) {
                $table->dropColumn('shortcode_body');
            }
            if (Schema::hasColumn('export_templates', 'template_type')) {
                $table->dropColumn('template_type');
            }
        });
    }
};
