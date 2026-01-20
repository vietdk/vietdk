<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->string('feed_url')->nullable();
            $table->json('selectors')->nullable();
            $table->string('schedule')->default('daily');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_sources');
    }
};
