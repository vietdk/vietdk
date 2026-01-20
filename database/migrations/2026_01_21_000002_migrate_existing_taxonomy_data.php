<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration prepares existing data for the schema changes by:
     * 1. Migrating multi-select tones to single-select (keeping first tone)
     * 2. Migrating multi-select campaigns to single-select (keeping first campaign)
     * 3. Adding temporary columns to store the selected IDs
     */
    public function up(): void
    {
        // Add temporary columns to store the single-select IDs
        if (!Schema::hasColumn('articles', 'tone_id')) {
            Schema::table('articles', function ($table) {
                $table->foreignId('tone_id')->nullable()->after('category_id');
            });
        }

        if (!Schema::hasColumn('articles', 'campaign_id')) {
            Schema::table('articles', function ($table) {
                $table->foreignId('campaign_id')->nullable()->after('tone_id');
            });
        }

        // Migrate existing tone relationships (keep the first one)
        DB::table('articles')->get()->each(function ($article) {
            $firstTone = DB::table('article_tone')
                ->where('article_id', $article->id)
                ->first();

            if ($firstTone) {
                DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['tone_id' => $firstTone->tone_id]);
            }
        });

        // Migrate existing campaign relationships (keep the first one)
        DB::table('articles')->get()->each(function ($article) {
            $firstCampaign = DB::table('article_campaign')
                ->where('article_id', $article->id)
                ->first();

            if ($firstCampaign) {
                DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['campaign_id' => $firstCampaign->campaign_id]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration doesn't restore the many-to-many relationships
     * as that data would have been lost in the subsequent schema migration.
     */
    public function down(): void
    {
        // Remove the columns if they exist
        Schema::table('articles', function ($table) {
            if (Schema::hasColumn('articles', 'campaign_id')) {
                $table->dropColumn('campaign_id');
            }
            if (Schema::hasColumn('articles', 'tone_id')) {
                $table->dropColumn('tone_id');
            }
        });
    }
};
