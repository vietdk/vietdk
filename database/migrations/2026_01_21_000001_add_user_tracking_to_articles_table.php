<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');

            // Add indexes for better query performance
            $table->index('updated_by');
            $table->index('approved_by');
            $table->index('rejected_by');
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['approved_by']);
            $table->dropIndex(['rejected_by']);
            $table->dropIndex(['status', 'updated_at']);
            $table->dropColumn(['updated_by', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at']);
        });
    }
};
