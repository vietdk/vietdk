<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->string('grouping_type')->default('none')->after('filters');
            $table->json('grouping_order')->nullable()->after('grouping_type');
            $table->boolean('show_group_headers')->default(true)->after('grouping_order');
            $table->string('group_header_format')->nullable()->after('show_group_headers');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn([
                'grouping_type',
                'grouping_order',
                'show_group_headers',
                'group_header_format',
            ]);
        });
    }
};
