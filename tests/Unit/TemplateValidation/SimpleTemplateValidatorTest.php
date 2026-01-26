<?php

namespace Tests\Unit\TemplateValidation;

use App\Services\TemplateValidation\SimpleTemplateValidator;
use App\Services\TemplateValidation\ValidationResult;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimpleTemplateValidatorTest extends TestCase
{
    protected SimpleTemplateValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SimpleTemplateValidator();
    }

    #[Test]
    public function it_validates_empty_template_as_invalid()
    {
        $result = $this->validator->validate('', 'html');

        $this->assertFalse($result->isValid);
        $this->assertContains('Template cannot be empty', $result->errors);
    }

    #[Test]
    public function it_validates_simple_valid_template()
    {
        $template = '{{#articles}}{{title}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_detects_unclosed_blocks()
    {
        $template = '{{#articles}}{{title}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Unclosed block', $result->errors[0] ?? '');
        $this->assertStringContainsString('{{#articles}}', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_unmatched_closing_tags()
    {
        $template = '{{title}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('no matching opening tag', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_mismatched_block_counts()
    {
        $template = '{{#articles}}{{title}}{{/articles}}{{#articles}}{{body}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('2 opening tag(s) but only 1 closing', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_validates_nested_blocks()
    {
        $template = '{{#articles}}{{#body_paragraphs}}{{paragraph}}{{/body_paragraphs}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_warns_about_unknown_placeholders()
    {
        $template = '{{#articles}}{{unknown_field}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid); // Warnings don't make it invalid
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('Unknown placeholder', $result->warnings[0] ?? '');
        $this->assertStringContainsString('{{unknown_field}}', $result->warnings[0] ?? '');
    }

    #[Test]
    public function it_accepts_known_placeholders()
    {
        $template = '{{#articles}}{{title}} {{body}} {{excerpt}} {{author}} {{category}} {{tags}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        // Should have no warnings about these placeholders
        $unknownWarnings = array_filter($result->warnings, fn($w) => str_contains($w, 'Unknown placeholder'));
        $this->assertEmpty($unknownWarnings);
    }

    #[Test]
    public function it_accepts_placeholders_with_pipe_modifiers()
    {
        $template = '{{#articles}}{{body_excerpt|200}} {{approved_at|Y-m-d}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_warns_when_articles_block_is_missing()
    {
        $template = '{{title}} {{body}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid); // Just a warning
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('{{#articles}}', $result->warnings[0] ?? '');
    }

    #[Test]
    public function it_does_not_warn_when_articles_block_is_present()
    {
        $template = '{{#articles}}{{title}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $articlesWarnings = array_filter($result->warnings, fn($w) => str_contains($w, '{{#articles}}'));
        $this->assertEmpty($articlesWarnings);
    }

    #[Test]
    public function it_detects_malformed_block_tags_with_space_after_opening()
    {
        $template = '{{ #articles}}{{title}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Malformed block tag', $result->errors[0] ?? '');
        $this->assertStringContainsString('space after {{', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_malformed_block_tags_with_space_before_tag_name()
    {
        $template = '{{# articles}}{{title}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Malformed block tag', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_validates_conditional_blocks()
    {
        $template = '{{#articles}}{{#if category}}{{category}}{{/if}}{{/articles}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_validates_group_by_category_block()
    {
        $template = '{{#group_by_category}}{{category_name}}{{#articles}}{{title}}{{/articles}}{{/group_by_category}}';
        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_validates_complex_real_world_template()
    {
        $template = <<<'TEMPLATE'
<h1>Bulletin</h1>
<p>{{export_date}}</p>
{{#group_by_category}}
<h2>{{category_name}}</h2>
{{#articles}}
<h3>{{title}}</h3>
<p>{{body_excerpt|500}}</p>
<p><em>{{source}}</em></p>
{{/articles}}
{{/group_by_category}}
TEMPLATE;

        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_validates_template_with_multiple_block_types()
    {
        $template = <<<'TEMPLATE'
{{#articles}}
{{#body_paragraphs}}
<p>{{paragraph}}</p>
{{/body_paragraphs}}
{{#if tags}}
<p>Tags: {{tags}}</p>
{{/if}}
{{/articles}}
TEMPLATE;

        $result = $this->validator->validate($template, 'html');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_returns_validation_result_object()
    {
        $result = $this->validator->validate('{{#articles}}{{title}}{{/articles}}', 'html');

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertIsBool($result->isValid);
        $this->assertIsArray($result->errors);
        $this->assertIsArray($result->warnings);
    }

    #[Test]
    public function it_provides_helper_methods_on_validation_result()
    {
        $invalidTemplate = '{{#articles}}{{title}}';
        $result = $this->validator->validate($invalidTemplate, 'html');

        $this->assertTrue($result->hasErrors());
        $this->assertIsString($result->getErrorMessages());
        $this->assertNotEmpty($result->getErrorMessages());
    }
}
