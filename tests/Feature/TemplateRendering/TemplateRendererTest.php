<?php

namespace Tests\Feature\TemplateRendering;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Exporter\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new TemplateRenderer();
    }

    #[Test]
    public function it_renders_simple_article_template()
    {
        $article = $this->createArticle([
            'title' => 'Test Article',
            'body' => 'Article body content',
        ]);

        $template = '{{#articles}}{{title}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('Test Article', $result);
    }

    #[Test]
    public function it_renders_multiple_articles()
    {
        $articles = collect([
            $this->createArticle(['title' => 'Article 1']),
            $this->createArticle(['title' => 'Article 2']),
            $this->createArticle(['title' => 'Article 3']),
        ]);

        $template = '{{#articles}}{{title}},{{/articles}}';
        $result = $this->renderer->render($template, $articles, []);

        $this->assertStringContainsString('Article 1', $result);
        $this->assertStringContainsString('Article 2', $result);
        $this->assertStringContainsString('Article 3', $result);
    }

    #[Test]
    public function it_renders_article_with_category()
    {
        $category = Category::factory()->create(['name' => 'Technology']);
        $article = $this->createArticle([
            'title' => 'Tech News',
            'category_id' => $category->id,
        ]);

        $template = '{{#articles}}{{category}}: {{title}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('Technology: Tech News', $result);
    }

    #[Test]
    public function it_renders_article_with_tags()
    {
        $article = $this->createArticle(['title' => 'Article']);
        $article->tags()->attach(Tag::factory()->create(['name' => 'Tag1'])->id);
        $article->tags()->attach(Tag::factory()->create(['name' => 'Tag2'])->id);
        $article->load('tags');

        $template = '{{#articles}}Tags: {{tags}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('Tag1', $result);
        $this->assertStringContainsString('Tag2', $result);
    }

    #[Test]
    public function it_renders_article_index()
    {
        $articles = collect([
            $this->createArticle(['title' => 'Article 1']),
            $this->createArticle(['title' => 'Article 2']),
        ]);

        $template = '{{#articles}}{{article_index}}. {{title}}\n{{/articles}}';
        $result = $this->renderer->render($template, $articles, []);

        $this->assertStringContainsString('1. Article 1', $result);
        $this->assertStringContainsString('2. Article 2', $result);
    }

    #[Test]
    public function it_renders_global_placeholders()
    {
        $article = $this->createArticle(['title' => 'Test']);
        $context = [
            'export_date' => now()->setDate(2026, 1, 24),
            'total_articles' => 5,
        ];

        $template = 'Date: {{export_date}} | Total: {{total_articles}}\n{{#articles}}{{title}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), $context);

        $this->assertStringContainsString('Date: 2026-01-24', $result);
        $this->assertStringContainsString('Total: 5', $result);
    }

    #[Test]
    public function it_renders_with_date_formatting()
    {
        $article = $this->createArticle([
            'title' => 'Test',
            'approved_at' => now()->setDate(2026, 1, 24)->setTime(14, 30),
        ]);

        $template = '{{#articles}}{{approved_at|Y-m-d}} {{approved_at|H:i}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('2026-01-24', $result);
        $this->assertStringContainsString('14:30', $result);
    }

    #[Test]
    public function it_renders_body_excerpt_with_truncation()
    {
        $article = $this->createArticle([
            'title' => 'Test',
            'body' => str_repeat('A', 500),
        ]);

        $template = '{{#articles}}{{body_excerpt|100}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertLessThanOrEqual(100, strlen(trim($result)));
    }

    #[Test]
    public function it_renders_title_uppercase()
    {
        $article = $this->createArticle(['title' => 'lowercase title']);

        $template = '{{#articles}}{{title_uppercase}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('LOWERCASE TITLE', $result);
    }

    #[Test]
    public function it_renders_tags_list_in_html()
    {
        $article = $this->createArticle(['title' => 'Test']);
        $article->tags()->attach(Tag::factory()->create(['name' => 'Tag1'])->id);
        $article->tags()->attach(Tag::factory()->create(['name' => 'Tag2'])->id);
        $article->load('tags');

        $template = '{{#articles}}{{tags_list}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), [], false);

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('Tag1', $result);
        $this->assertStringContainsString('Tag2', $result);
    }

    #[Test]
    public function it_renders_tags_list_in_plain_text()
    {
        $article = $this->createArticle(['title' => 'Test']);
        $article->tags()->attach(Tag::factory()->create(['name' => 'Tag1'])->id);
        $article->tags()->attach(Tag::factory()->create(['name' => 'Tag2'])->id);
        $article->load('tags');

        $template = '{{#articles}}{{tags_list}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), [], true);

        $this->assertStringContainsString('- Tag1', $result);
        $this->assertStringContainsString('- Tag2', $result);
        $this->assertStringNotContainsString('<ul>', $result);
    }

    #[Test]
    public function it_renders_body_paragraphs_block()
    {
        $article = $this->createArticle([
            'title' => 'Test',
            'body' => "Paragraph 1\n\nParagraph 2\n\nParagraph 3",
        ]);

        $template = '{{#articles}}{{#body_paragraphs}}<p>{{paragraph}}</p>{{/body_paragraphs}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('Paragraph 1', $result);
        $this->assertStringContainsString('Paragraph 2', $result);
        $this->assertStringContainsString('Paragraph 3', $result);
        $this->assertEquals(3, substr_count($result, '<p>'));
    }

    #[Test]
    public function it_renders_conditional_if_blocks()
    {
        $withExcerpt = $this->createArticle([
            'title' => 'With Excerpt',
            'excerpt' => 'Has excerpt',
        ]);
        $withoutExcerpt = $this->createArticle([
            'title' => 'Without Excerpt',
            'excerpt' => null,
        ]);

        $template = '{{#articles}}{{#if excerpt}}Has excerpt{{/if}}|{{/articles}}';
        $result = $this->renderer->render($template, collect([$withExcerpt, $withoutExcerpt]), []);

        $this->assertStringContainsString('Has excerpt', $result);
    }

    #[Test]
    public function it_renders_group_by_category()
    {
        $category1 = Category::factory()->create(['name' => 'Category A']);
        $category2 = Category::factory()->create(['name' => 'Category B']);

        $articles = collect([
            $this->createArticle(['title' => 'Article 1', 'category_id' => $category1->id]),
            $this->createArticle(['title' => 'Article 2', 'category_id' => $category2->id]),
            $this->createArticle(['title' => 'Article 3', 'category_id' => $category1->id]),
        ]);

        $template = '{{#group_by_category}}<h3>{{category_name}}</h3>{{#articles}}{{title}},{{/articles}}{{/group_by_category}}';
        $result = $this->renderer->render($template, $articles, []);

        $this->assertStringContainsString('Category A', $result);
        $this->assertStringContainsString('Category B', $result);
        $this->assertStringContainsString('Article 1', $result);
        $this->assertStringContainsString('Article 2', $result);
        $this->assertStringContainsString('Article 3', $result);
    }

    #[Test]
    public function it_strips_html_in_plain_text_mode()
    {
        $article = $this->createArticle([
            'title' => 'Test',
            'body' => '<p>Paragraph with <strong>bold</strong> text</p>',
        ]);

        $template = '{{#articles}}{{body}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), [], true);

        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringContainsString('Paragraph with bold text', $result);
    }

    #[Test]
    public function it_preserves_html_in_html_mode()
    {
        $article = $this->createArticle([
            'title' => 'Test',
            'body' => '<p>Paragraph with <strong>bold</strong> text</p>',
        ]);

        $template = '{{#articles}}{{body}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), [], false);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
    }

    #[Test]
    public function it_handles_empty_article_collection()
    {
        $template = '{{#articles}}{{title}}{{/articles}}';
        $result = $this->renderer->render($template, collect([]), []);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('{{', $result);
    }

    #[Test]
    public function it_handles_missing_placeholders_gracefully()
    {
        $article = $this->createArticle(['title' => 'Test']);

        $template = '{{#articles}}{{title}} - {{nonexistent}}{{/articles}}';
        $result = $this->renderer->render($template, collect([$article]), []);

        $this->assertStringContainsString('Test', $result);
        $this->assertStringNotContainsString('{{nonexistent}}', $result);
    }

    protected function createArticle(array $attributes = []): Article
    {
        $defaults = [
            'title' => 'Default Title',
            'body' => 'Default body content',
            'excerpt' => 'Default excerpt',
            'status' => Article::STATUS_APPROVED,
            'approved_at' => now(),
            'author_id' => User::factory()->create()->id,
        ];

        $article = Article::factory()->create(array_merge($defaults, $attributes));
        $article->load(['category', 'tags', 'author']);

        return $article;
    }
}
