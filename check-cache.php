<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "==============================================\n";
echo "Checking Crawler Default Values\n";
echo "==============================================\n\n";

echo "1. Cache Driver: " . config('cache.default') . "\n\n";

echo "2. Cached Values:\n";
echo "   category_id: " . (Cache::get('crawler.defaults.category_id') ?: 'NULL') . "\n";
echo "   tone_id: " . (Cache::get('crawler.defaults.tone_id') ?: 'NULL') . "\n";
echo "   campaign_id: " . (Cache::get('crawler.defaults.campaign_id') ?: 'NULL') . "\n";
echo "   system_user_id: " . (Cache::get('crawler.defaults.system_user_id') ?: 'NULL') . "\n\n";

echo "3. Database Values:\n";
$category = DB::table('categories')->where('slug', 'crawled')->first();
echo "   Category 'crawled': " . ($category ? "ID {$category->id}" : "NOT FOUND") . "\n";

$tone = DB::table('tones')->where('slug', 'neutral')->first();
echo "   Tone 'neutral': " . ($tone ? "ID {$tone->id}" : "NOT FOUND") . "\n";

$campaign = DB::table('campaigns')->where('slug', 'general')->first();
echo "   Campaign 'general': " . ($campaign ? "ID {$campaign->id}" : "NOT FOUND") . "\n";

$user = DB::table('users')->where('email', 'system@crawler.local')->first();
echo "   System user: " . ($user ? "ID {$user->id}" : "NOT FOUND") . "\n\n";

if (!Cache::get('crawler.defaults.category_id') && $category) {
    echo "4. Issue Found: Database values exist but cache is empty!\n";
    echo "   Solution: Run the seeder again to populate cache:\n";
    echo "   php artisan db:seed --class=CrawlerDefaultsSeeder\n";
}

echo "\n==============================================\n";
