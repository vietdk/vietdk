# Template System Testing Guide

**Date:** 2026-01-24
**Version:** 1.0
**Test Suite Size:** 80 automated tests

---

## Overview

This guide explains how to run and understand the automated test suite for the CMS template system. The test suite covers template validation, rendering, and complete export workflows.

---

## Test Structure

```
tests/
├── Unit/
│   └── TemplateValidation/
│       ├── SimpleTemplateValidatorTest.php      (22 tests)
│       └── ShortcodeTemplateValidatorTest.php   (26 tests)
└── Feature/
    ├── TemplateRendering/
    │   └── TemplateRendererTest.php             (24 tests)
    └── BulletinExportWorkflowTest.php           (8 tests)

Total: 80 tests
```

---

## Running Tests

### Prerequisites

Ensure your test environment is set up:

```bash
# Copy environment configuration
cp .env .env.testing

# Update database settings in .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Run migrations (if needed)
php artisan migrate --env=testing
```

### Run All Tests

```bash
# Run the complete test suite
php artisan test

# Run with coverage (requires Xdebug)
php artisan test --coverage

# Run with detailed output
php artisan test --verbose
```

### Run Specific Test Suites

```bash
# Unit tests only (validators)
php artisan test tests/Unit/TemplateValidation

# Integration tests only (rendering)
php artisan test tests/Feature/TemplateRendering

# End-to-end tests only (workflow)
php artisan test tests/Feature/BulletinExportWorkflowTest

# Specific test file
php artisan test tests/Unit/TemplateValidation/SimpleTemplateValidatorTest.php

# Specific test method
php artisan test --filter=it_validates_simple_valid_template
```

### Run Tests with Filters

```bash
# Run all tests with "validation" in the name
php artisan test --filter=validation

# Run all tests with "category" in the name
php artisan test --filter=category

# Run all tests in parallel (faster)
php artisan test --parallel
```

---

## Test Coverage Breakdown

### Unit Tests: Template Validation (48 tests)

#### SimpleTemplateValidatorTest (22 tests)

Tests the Mustache-style template validator (`{{placeholder}}` syntax).

**Key Test Cases:**
- ✅ Empty template detection
- ✅ Balanced block tags (`{{#articles}}...{{/articles}}`)
- ✅ Unclosed block detection
- ✅ Unknown placeholder warnings
- ✅ Malformed tag detection
- ✅ Nested blocks support
- ✅ Conditional blocks (`{{#if}}`)
- ✅ Group by category blocks
- ✅ Pipe modifiers (`{{body|200}}`)
- ✅ Complex real-world templates

**Example Test:**
```php
/** @test */
public function it_detects_unclosed_blocks()
{
    $template = '{{#articles}}{{title}}';
    $result = $this->validator->validate($template, 'html');

    $this->assertFalse($result->isValid);
    $this->assertStringContainsString('Unclosed block', $result->errors[0] ?? '');
}
```

#### ShortcodeTemplateValidatorTest (26 tests)

Tests the shortcode template validator (`[list_posts]` syntax).

**Key Test Cases:**
- ✅ Empty template detection
- ✅ Balanced shortcode tags (`[list_posts]...[/list_posts]`)
- ✅ Base64 args validation
- ✅ JSON structure validation
- ✅ Token syntax (`%%post_data.field%%`)
- ✅ Taxonomy token format (`%%taxonomy.key.0.field%%`)
- ✅ Unknown token prefix warnings
- ✅ Malformed shortcode detection
- ✅ Nested blocks ([html], [time], [section])
- ✅ Complex real-world templates

**Example Test:**
```php
/** @test */
public function it_detects_invalid_base64_in_args()
{
    $template = '[list_posts args="invalid!!!base64"][loop]Content[/loop][/list_posts]';
    $result = $this->validator->validate($template, 'shortcode');

    $this->assertFalse($result->isValid);
    $this->assertStringContainsString('invalid Base64', $result->errors[0] ?? '');
}
```

---

### Integration Tests: Template Rendering (24 tests)

#### TemplateRendererTest (24 tests)

Tests the actual rendering of templates with Article models.

**Key Test Cases:**
- ✅ Simple article rendering
- ✅ Multiple articles
- ✅ Articles with categories and tags
- ✅ Article index numbering
- ✅ Global placeholders (export_date, total_articles)
- ✅ Date formatting
- ✅ Body truncation (`{{body_excerpt|200}}`)
- ✅ Title transformations (`{{title_uppercase}}`)
- ✅ Tags list (HTML `<ul><li>` and plain text bullets)
- ✅ Body paragraphs block
- ✅ Conditional blocks (`{{#if category}}`)
- ✅ Category grouping
- ✅ HTML vs plain text modes
- ✅ Empty collections
- ✅ Missing placeholders

**Example Test:**
```php
/** @test */
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
```

**Database Setup:**
- Uses `RefreshDatabase` trait
- Creates fresh database for each test
- Uses factories for realistic test data
- Loads relationships (category, tags, author)

---

### End-to-End Tests: Bulletin Export Workflow (8 tests)

#### BulletinExportWorkflowTest (8 tests)

Tests complete workflows from template creation to export.

**Key Test Cases:**
- ✅ **Complete workflow:**
  - Create ExportTemplate
  - Create sample articles
  - Validate template
  - Render HTML and text
  - Export to storage
  - Verify output
- ✅ Validation before rendering
- ✅ Category grouping export
- ✅ Empty article handling
- ✅ All placeholder types in one template
- ✅ Template duplication
- ✅ XSS prevention

**Example Test:**
```php
/** @test */
public function it_completes_full_export_workflow_with_simple_template()
{
    // 1. Create template
    $template = ExportTemplate::create([...]);

    // 2. Create articles
    $articles = collect([...]);

    // 3. Validate
    $validator = new SimpleTemplateValidator();
    $result = $validator->validate($template->html_body, 'html');
    $this->assertTrue($result->isValid);

    // 4. Render
    $output = $renderer->render($template->html_body, $articles, [...]);

    // 5. Export
    Storage::disk('local')->put('exports/test.txt', $output);

    // 6. Verify
    $this->assertTrue(Storage::disk('local')->exists('exports/test.txt'));
}
```

**Test Environment:**
- Uses `RefreshDatabase` for clean state
- Uses `Storage::fake('local')` for file operations
- Creates realistic export scenarios
- Verifies security (XSS prevention)

---

## Understanding Test Results

### Successful Test Run

```
PASS  Tests\Unit\TemplateValidation\SimpleTemplateValidatorTest
✓ it validates empty template as invalid
✓ it validates simple valid template
✓ it detects unclosed blocks
... (19 more tests)

PASS  Tests\Unit\TemplateValidation\ShortcodeTemplateValidatorTest
✓ it validates empty template as invalid
✓ it validates simple valid shortcode template
✓ it detects unclosed list posts tag
... (23 more tests)

PASS  Tests\Feature\TemplateRendering\TemplateRendererTest
✓ it renders simple article template
✓ it renders multiple articles
... (22 more tests)

PASS  Tests\Feature\BulletinExportWorkflowTest
✓ it completes full export workflow with simple template
✓ it validates template before rendering
... (6 more tests)

Tests:    80 passed
Duration: 2.45s
```

### Failed Test Example

```
FAIL  Tests\Unit\TemplateValidation\SimpleTemplateValidatorTest
✗ it detects unclosed blocks

Expected false to be true.
Failed asserting that true is false.

at tests/Unit/TemplateValidation/SimpleTemplateValidatorTest.php:45
```

**How to Debug:**
1. Read the assertion message
2. Check the test method code
3. Run the specific test with `--verbose`
4. Add `dump()` or `dd()` to inspect values
5. Check the validator implementation

---

## Writing New Tests

### Test File Template

```php
<?php

namespace Tests\Unit\MyFeature;

use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup code here
    }

    /** @test */
    public function it_does_something()
    {
        // Arrange
        $input = 'test data';

        // Act
        $result = $this->myMethod($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Best Practices

1. **Use descriptive test names**
   - ✅ `it_detects_unclosed_blocks`
   - ❌ `test1`

2. **Follow Arrange-Act-Assert pattern**
   ```php
   // Arrange - set up test data
   $template = '{{#articles}}...{{/articles}}';

   // Act - perform the action
   $result = $validator->validate($template, 'html');

   // Assert - verify the outcome
   $this->assertTrue($result->isValid);
   ```

3. **Test one thing per test**
   - Each test should verify one specific behavior
   - Makes failures easier to diagnose

4. **Use factories for models**
   ```php
   $article = Article::factory()->create(['title' => 'Test']);
   ```

5. **Clean up with RefreshDatabase**
   ```php
   use RefreshDatabase;
   ```

---

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

### Local Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit

echo "Running tests..."
php artisan test

if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi
```

---

## Test Coverage Analysis

### Generate Coverage Report

```bash
# HTML coverage report (requires Xdebug)
php artisan test --coverage-html coverage

# Open in browser
open coverage/index.html

# Text coverage report
php artisan test --coverage-text
```

### Coverage Goals

- **Validators:** 100% coverage ✅
- **Renderers:** 90%+ coverage ✅
- **Controllers:** 70%+ coverage
- **Overall:** 80%+ coverage

---

## Troubleshooting

### Tests Fail: Database Issues

```bash
# Clear database
php artisan migrate:fresh --env=testing

# Check database connection
php artisan tinker --env=testing
>>> DB::connection()->getPdo()
```

### Tests Fail: Missing Dependencies

```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### Tests Slow

```bash
# Run in parallel (requires package)
composer require --dev brianium/paratest
php artisan test --parallel

# Run only fast tests
php artisan test --exclude-group=slow
```

### Memory Issues

```bash
# Increase memory limit
php -d memory_limit=512M artisan test
```

---

## Test Maintenance

### When to Update Tests

- ✅ When adding new features
- ✅ When fixing bugs (add regression test)
- ✅ When refactoring code (ensure tests still pass)
- ✅ When changing APIs or interfaces

### Regular Maintenance

```bash
# Run tests before commits
php artisan test

# Run tests before deployments
php artisan test --coverage

# Review test coverage monthly
php artisan test --coverage-html coverage
```

---

## Additional Resources

### Documentation
- `/docs/template-creation-guide.md` - User guide for templates
- `/docs/client-template-testing-checklist.md` - Manual testing checklist
- `README.md` - Project overview

### Code References
- `app/Services/TemplateValidation/` - Validator implementations
- `app/Services/Exporter/TemplateRenderer.php` - Rendering logic
- `app/Services/Shortcodes/ShortcodeTemplateRenderer.php` - Shortcode rendering

### Laravel Testing Docs
- https://laravel.com/docs/testing
- https://laravel.com/docs/database-testing
- https://laravel.com/docs/mocking

---

## Summary

**Test Suite:** 80 automated tests
**Coverage:** Validators, rendering, workflows
**Execution Time:** ~2-3 seconds
**Maintenance:** Low (stable, well-structured)

The test suite ensures the template system is reliable, secure, and functions correctly across all use cases. Run tests frequently during development and before deployments to catch issues early.

**Quick Commands:**
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific suite
php artisan test tests/Unit/TemplateValidation

# Run in parallel (fast)
php artisan test --parallel
```

---

**Document Version:** 1.0
**Last Updated:** 2026-01-24
**Maintainer:** Development Team
