<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Tone;
use App\Models\Campaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CrawlerDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Create default taxonomy values for auto-created articles from crawler.
     */
    public function run(): void
    {
        // Create "Crawled" category
        $category = Category::firstOrCreate(
            ['slug' => 'crawled'],
            [
                'name' => 'Crawled',
                'description' => 'Articles automatically created from crawler',
            ]
        );

        // Create "Neutral" tone
        $tone = Tone::firstOrCreate(
            ['slug' => 'neutral'],
            [
                'name' => 'Neutral',
                'description' => 'Neutral tone for crawled articles',
            ]
        );

        // Create "General" campaign
        $campaign = Campaign::firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'description' => 'General campaign for uncategorized articles',
                'is_active' => true,
            ]
        );

        // Create system user for CLI crawls
        $systemUser = User::firstOrCreate(
            ['email' => 'system@crawler.local'],
            [
                'name' => 'System Crawler',
                'password' => bcrypt(bin2hex(random_bytes(32))), // Random password
                'role' => User::ROLE_WRITER,
            ]
        );

        // Cache the default IDs for quick lookup
        Cache::forever('crawler.defaults.category_id', $category->id);
        Cache::forever('crawler.defaults.tone_id', $tone->id);
        Cache::forever('crawler.defaults.campaign_id', $campaign->id);
        Cache::forever('crawler.defaults.system_user_id', $systemUser->id);

        $this->command->info('Crawler defaults seeded successfully!');
        $this->command->info("Category ID: {$category->id} (crawled)");
        $this->command->info("Tone ID: {$tone->id} (neutral)");
        $this->command->info("Campaign ID: {$campaign->id} (general)");
        $this->command->info("System User ID: {$systemUser->id} (system@crawler.local)");
    }
}
