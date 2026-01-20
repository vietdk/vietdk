<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('article_tone', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('tone_id')->constrained()->onDelete('cascade');
            $table->primary(['article_id', 'tone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tone');
        Schema::dropIfExists('tones');
    }
};
