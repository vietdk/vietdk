<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawled_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('url')->unique();
            $table->timestamp('published_date')->nullable();
            $table->enum('status', ['new', 'used', 'skipped'])->default('new');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('news_source_id');
        });

        // Add foreign key to articles table
        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('source_metadata_id')
                ->references('id')
                ->on('crawled_metadata')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['source_metadata_id']);
        });

        Schema::dropIfExists('crawled_metadata');
    }
};
