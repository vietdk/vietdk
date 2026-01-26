<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\ExportTemplate;
use App\Models\Tag;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ExportTemplatePolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default timezone for all date/time operations
        date_default_timezone_set(config('app.timezone'));

        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(ExportTemplate::class, ExportTemplatePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);

        RateLimiter::for('export', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('crawler', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(5)->by($key);
        });
    }
}
