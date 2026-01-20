<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration makes structural changes to the articles table:
     * 1. Removes slug field (auto-generation not needed)
     * 2. Adds original_title and original_url fields
     * 3. Makes category_id required
     * 4. Adds foreign key constraints to tone_id and campaign_id
     * 5. Drops the pivot tables for tones and campaigns
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Remove slug field
            if (Schema::hasColumn('articles', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }

            // Add new content fields (check if they don't already exist)
            if (!Schema::hasColumn('articles', 'original_title')) {
                $table->string('original_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('articles', 'original_url')) {
                $table->text('original_url')->nullable()->after('original_title');
            }

            // Make category required - first drop existing foreign key, then modify and recreate
            $table->dropForeign(['category_id']);
            $table->foreignId('category_id')->nullable(false)->change();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');

            // Add foreign key constraints to tone_id and campaign_id
            // (These columns were already added in the previous migration)
            if (Schema::hasColumn('articles', 'tone_id')) {
                $table->foreign('tone_id')->references('id')->on('tones')->onDelete('set null');
            }

            if (Schema::hasColumn('articles', 'campaign_id')) {
                $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            }
        });

        // Drop pivot tables (we no longer need them)
        Schema::dropIfExists('article_tone');
        Schema::dropIfExists('article_campaign');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate pivot tables
        Schema::create('article_tone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('tone_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['article_id', 'tone_id']);
        });

        Schema::create('article_campaign', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['article_id', 'campaign_id']);
        });

        Schema::table('articles', function (Blueprint $table) {
            // Remove foreign key constraints
            if (Schema::hasColumn('articles', 'tone_id')) {
                $table->dropForeign(['tone_id']);
            }

            if (Schema::hasColumn('articles', 'campaign_id')) {
                $table->dropForeign(['campaign_id']);
            }

            // Make category_id nullable again - drop and recreate with set null
            $table->dropForeign(['category_id']);
            $table->foreignId('category_id')->nullable()->change();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');

            // Remove new content fields
            $table->dropColumn(['original_title', 'original_url']);

            // Add back slug column
            $table->string('slug')->unique()->after('title');
        });
    }
};
