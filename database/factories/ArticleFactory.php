<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'body' => fake()->paragraphs(3, true),
            'excerpt' => fake()->optional()->sentence(),
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ];
    }
}
