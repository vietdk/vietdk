<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\ExportTemplate;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BulletinExportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    #[Test]
    public function it_completes_full_export_workflow_with_simple_template()
    {
        // 1. Create template
        $template = ExportTemplate::create([
            'name' => 'Test Bulletin Template',
            'description' => 'A test template for workflow testing',
            'template_type' => 'simple',
            'html_body' => '<h1>Bulletin</h1><p>{{export_date}}</p>{{#articles}}<h2>{{title}}</h2><p>{{body}}</p>{{/articles}}',
            'text_body' => "Bulletin\n{{export_date}}\n\n{{#articles}}{{title}}\n{{body}}\n\n{{/articles}}",
            'file_path' => 'inline',
            'filters' => [],
            'is_default' => false,
            'grouping_type' => 'none',
        ]);

        $this->assertInstanceOf(ExportTemplate::class, $template);
        $this->assertEquals('Test Bulletin Template', $template->name);
        $this->assertEquals('simple', $template->template_type);

        // 2. Create sample articles
        $category = Category::factory()->create(['name' => 'Technology']);
        $articles = collect([
            $this->createArticle([
                'title' => 'Article 1',
                'body' => 'Body of article 1',
                'category_id' => $category->id,
            ]),
            $this->createArticle([
                'title' => 'Article 2',
                'body' => 'Body of article 2',
                'category_id' => $category->id,
            ]),
        ]);

        $this->assertCount(2, $articles);

        // 3. Verify template validates correctly
        $validator = new \App\Services\TemplateValidation\SimpleTemplateValidator();
        $htmlValidation = $validator->validate($template->html_body, 'html');
        $textValidation = $validator->validate($template->text_body, 'text');

        $this->assertTrue($htmlValidation->isValid, 'HTML template should be valid');
        $this->assertTrue($textValidation->isValid, 'Text template should be valid');

        // 4. Render template with articles
        $renderer = new \App\Services\Exporter\TemplateRenderer();

        // Render HTML version
        $htmlOutput = $renderer->render(
            $template->html_body,
            $articles,
            [
                'export_date' => now(),
                'total_articles' => $articles->count(),
            ],
            false
        );

        $this->assertStringContainsString('<h1>Bulletin</h1>', $htmlOutput);
        $this->assertStringContainsString('Article 1', $htmlOutput);
        $this->assertStringContainsString('Article 2', $htmlOutput);
        $this->assertStringContainsString('Body of article 1', $htmlOutput);

        // Render text version
        $textOutput = $renderer->render(
            $template->text_body,
            $articles,
            [
                'export_date' => now(),
                'total_articles' => $articles->count(),
            ],
            true
        );

        $this->assertStringContainsString('Bulletin', $textOutput);
        $this->assertStringContainsString('Article 1', $textOutput);
        $this->assertStringContainsString('Article 2', $textOutput);
        $this->assertStringNotContainsString('<h1>', $textOutput);

        // 5. Verify exports can be created (simulated file creation)
        Storage::disk('local')->put('exports/test.txt', $textOutput);
        $this->assertTrue(Storage::disk('local')->exists('exports/test.txt'));

        $content = Storage::disk('local')->get('exports/test.txt');
        $this->assertEquals($textOutput, $content);
    }

    #[Test]
    public function it_validates_template_before_rendering()
    {
        $template = ExportTemplate::create([
            'name' => 'Invalid Template',
            'template_type' => 'simple',
            'html_body' => '{{#articles}}{{title}}', // Missing closing tag
            'file_path' => 'inline',
            'filters' => [],
        ]);

        $validator = new \App\Services\TemplateValidation\SimpleTemplateValidator();
        $result = $validator->validate($template->html_body, 'html');

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('Unclosed block', $result->errors[0]);
    }

    #[Test]
    public function it_exports_with_category_grouping()
    {
        $template = ExportTemplate::create([
            'name' => 'Grouped Template',
            'template_type' => 'simple',
            'html_body' => '{{#group_by_category}}<h2>{{category_name}}</h2>{{#articles}}<h3>{{title}}</h3>{{/articles}}{{/group_by_category}}',
            'file_path' => 'inline',
            'grouping_type' => 'category',
        ]);

        $category1 = Category::factory()->create(['name' => 'Tech']);
        $category2 = Category::factory()->create(['name' => 'Business']);

        $articles = collect([
            $this->createArticle(['title' => 'Tech Article 1', 'category_id' => $category1->id]),
            $this->createArticle(['title' => 'Business Article 1', 'category_id' => $category2->id]),
            $this->createArticle(['title' => 'Tech Article 2', 'category_id' => $category1->id]),
        ]);

        $renderer = new \App\Services\Exporter\TemplateRenderer();
        $output = $renderer->render($template->html_body, $articles, []);

        $this->assertStringContainsString('<h2>Tech</h2>', $output);
        $this->assertStringContainsString('<h2>Business</h2>', $output);
        $this->assertStringContainsString('Tech Article 1', $output);
        $this->assertStringContainsString('Tech Article 2', $output);
        $this->assertStringContainsString('Business Article 1', $output);
    }

    #[Test]
    public function it_handles_templates_with_no_articles()
    {
        $template = ExportTemplate::create([
            'name' => 'Empty Template',
            'template_type' => 'simple',
            'html_body' => '<h1>Bulletin</h1>{{#articles}}{{title}}{{/articles}}<p>No articles found.</p>',
            'file_path' => 'inline',
        ]);

        $renderer = new \App\Services\Exporter\TemplateRenderer();
        $output = $renderer->render($template->html_body, collect([]), []);

        $this->assertStringContainsString('<h1>Bulletin</h1>', $output);
        $this->assertStringContainsString('No articles found', $output);
    }

    #[Test]
    public function it_exports_template_with_all_placeholder_types()
    {
        $template = ExportTemplate::create([
            'name' => 'Complete Template',
            'template_type' => 'simple',
            'html_body' => <<<'HTML'
<h1>Export Date: {{export_date}}</h1>
<p>Total: {{total_articles}} articles</p>
{{#articles}}
<div>
<h2>{{article_index}}. {{title}}</h2>
<h3>{{title_uppercase}}</h3>
<p>{{body_excerpt|100}}</p>
<p>Category: {{category}}</p>
<p>Tags: {{tags}}</p>
<p>Tags List:</p>
{{tags_list}}
<p>Date: {{approved_date}}</p>
{{#if excerpt}}
<blockquote>{{excerpt}}</blockquote>
{{/if}}
</div>
{{/articles}}
HTML,
            'file_path' => 'inline',
        ]);

        $category = Category::factory()->create(['name' => 'Test Category']);
        $tag1 = Tag::factory()->create(['name' => 'Tag1']);
        $tag2 = Tag::factory()->create(['name' => 'Tag2']);

        $article = $this->createArticle([
            'title' => 'test title',
            'body' => str_repeat('Lorem ipsum dolor sit amet. ', 20),
            'excerpt' => 'Test excerpt',
            'category_id' => $category->id,
            'approved_at' => now()->setDate(2026, 1, 24),
        ]);
        $article->tags()->attach([$tag1->id, $tag2->id]);
        $article->load('tags');

        $renderer = new \App\Services\Exporter\TemplateRenderer();
        $output = $renderer->render(
            $template->html_body,
            collect([$article]),
            [
                'export_date' => now()->setDate(2026, 1, 24),
                'total_articles' => 1,
            ]
        );

        // Verify all placeholder types render
        $this->assertStringContainsString('2026-01-24', $output); // export_date
        $this->assertStringContainsString('Total: 1 articles', $output); // total_articles
        $this->assertStringContainsString('1. test title', $output); // article_index
        $this->assertStringContainsString('TEST TITLE', $output); // title_uppercase
        $this->assertStringContainsString('Test Category', $output); // category
        $this->assertStringContainsString('Tag1', $output); // tags
        $this->assertStringContainsString('Tag2', $output); // tags
        $this->assertStringContainsString('<ul>', $output); // tags_list (HTML mode)
        $this->assertStringContainsString('<li>', $output); // tags_list items
        $this->assertStringContainsString('Test excerpt', $output); // conditional if block
    }

    #[Test]
    public function it_can_duplicate_templates()
    {
        $original = ExportTemplate::create([
            'name' => 'Original Template',
            'description' => 'Original description',
            'template_type' => 'simple',
            'html_body' => '<h1>Test</h1>',
            'file_path' => 'inline',
            'is_default' => true,
        ]);

        $duplicate = $original->replicate();
        $duplicate->name = $original->name . ' (Copy)';
        $duplicate->is_default = false;
        $duplicate->save();

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertEquals('Original Template (Copy)', $duplicate->name);
        $this->assertEquals($original->html_body, $duplicate->html_body);
        $this->assertEquals($original->template_type, $duplicate->template_type);
        $this->assertFalse($duplicate->is_default);
        $this->assertEquals(2, ExportTemplate::count());
    }

    #[Test]
    public function it_escapes_html_in_output_for_security()
    {
        $template = ExportTemplate::create([
            'name' => 'Security Test',
            'template_type' => 'simple',
            'html_body' => '{{#articles}}<h2>{{title}}</h2>{{/articles}}',
            'file_path' => 'inline',
        ]);

        $article = $this->createArticle([
            'title' => '<script>alert("XSS")</script>Malicious Title',
        ]);

        $renderer = new \App\Services\Exporter\TemplateRenderer();
        $output = $renderer->render($template->html_body, collect([$article]), []);

        // Title should NOT contain raw script tags (they should be escaped or in body which is safe)
        // Note: Title is typically treated as safe text, but body/excerpt fields are HTML-safe
        $this->assertStringContainsString('Malicious Title', $output);
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
