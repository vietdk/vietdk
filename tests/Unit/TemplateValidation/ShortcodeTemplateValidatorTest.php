<?php

namespace Tests\Unit\TemplateValidation;

use App\Services\TemplateValidation\ShortcodeTemplateValidator;
use App\Services\TemplateValidation\ValidationResult;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShortcodeTemplateValidatorTest extends TestCase
{
    protected ShortcodeTemplateValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ShortcodeTemplateValidator();
    }

    #[Test]
    public function it_validates_empty_template_as_invalid()
    {
        $result = $this->validator->validate('', 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertContains('Template cannot be empty', $result->errors);
    }

    #[Test]
    public function it_validates_simple_valid_shortcode_template()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%post_data.post_title%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_detects_unclosed_list_posts_tag()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]Content[/loop]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Unclosed shortcode', $result->errors[0] ?? '');
        $this->assertStringContainsString('[list_posts]', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_unclosed_loop_tag()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]Content[/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('[loop]', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_mismatched_tag_counts()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]Content[/loop][/list_posts][list_posts args=\"{$args}\"][loop]More[/loop]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('2 opening tag(s) but only 1 closing', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_closing_tag_without_opening()
    {
        $template = "Content[/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('no matching opening tag', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_invalid_base64_in_args()
    {
        $template = '[list_posts args="invalid!!!base64"][loop]Content[/loop][/list_posts]';
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('invalid Base64', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_invalid_json_in_args()
    {
        $invalidJson = base64_encode('{invalid json}');
        $template = "[list_posts args=\"{$invalidJson}\"][loop]Content[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('invalid JSON', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_empty_args_attribute()
    {
        $template = '[list_posts args=""][loop]Content[/loop][/list_posts]';
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('empty args', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_validates_proper_base64_json_args()
    {
        $args = base64_encode(json_encode([
            'tax_query' => [
                'relation' => 'AND',
                ['taxonomy' => 'category', 'field' => 'id', 'terms' => [1, 2, 3]],
            ],
        ]));
        $template = "[list_posts args=\"{$args}\"][loop]%%post_data.post_title%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_validates_post_data_tokens()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%post_data.post_title%% %%post_data.post_content%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->warnings);
    }

    #[Test]
    public function it_validates_taxonomy_tokens()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%taxonomy.category.0.name%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_warns_about_malformed_taxonomy_tokens()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%taxonomy.category%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid); // Warnings don't invalidate
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('should have format', $result->warnings[0] ?? '');
    }

    #[Test]
    public function it_validates_post_meta_tokens()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%post_meta.custom_field%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_warns_about_unknown_token_prefixes()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%unknown_prefix.field%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid); // Warnings only
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('Unknown token prefix', $result->warnings[0] ?? '');
    }

    #[Test]
    public function it_accepts_url_and_text_tokens()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%url%% %%text%%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_detects_malformed_shortcode_tags_with_space()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[ list_posts args=\"{$args}\"][loop]Content[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Malformed shortcode', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_detects_unmatched_token_delimiters()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]%%post_data.title%[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Unmatched %% token delimiter', $result->errors[0] ?? '');
    }

    #[Test]
    public function it_validates_html_shortcode_blocks()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop][html]<p>Content</p>[/html][/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_validates_time_shortcode_blocks()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop][time format=\"Y-m-d\"]%%post_data.post_date%%[/time][/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_validates_section_shortcode_blocks()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[section][list_posts args=\"{$args}\"][loop]Content[/loop][/list_posts][/section]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function it_warns_about_unknown_shortcodes()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop][unknown_tag]Content[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid); // Just a warning
        $this->assertNotEmpty($result->warnings);
        $this->assertStringContainsString('Unknown shortcode', $result->warnings[0] ?? '');
    }

    #[Test]
    public function it_validates_complex_real_world_template()
    {
        $args = base64_encode(json_encode([
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'category',
                    'field' => 'id',
                    'operator' => 'IN',
                    'terms' => [1, 2, 3],
                    'children' => 'true',
                ],
            ],
        ]));

        $template = <<<TEMPLATE
[section]
[list_posts args="{$args}"]
[loop]
[html]
<h3>%%post_data.post_title%%</h3>
<p>%%post_data.post_excerpt%%</p>
<p><em>%%taxonomy.category.0.name%%</em></p>
[/html]
[/loop]
[/list_posts]
[/section]
TEMPLATE;

        $result = $this->validator->validate($template, 'shortcode');

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    #[Test]
    public function it_returns_validation_result_object()
    {
        $args = base64_encode(json_encode(['tax_query' => []]));
        $template = "[list_posts args=\"{$args}\"][loop]Content[/loop][/list_posts]";
        $result = $this->validator->validate($template, 'shortcode');

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertIsBool($result->isValid);
        $this->assertIsArray($result->errors);
        $this->assertIsArray($result->warnings);
    }
}
