<?php

namespace Tests\Unit\Shortcodes;

use App\Services\Shortcodes\TaxonomyRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxonomyRegistryTest extends TestCase
{
    #[Test]
    public function it_returns_all_taxonomy_definitions(): void
    {
        $all = TaxonomyRegistry::all();

        $this->assertArrayHasKey('category', $all);
        $this->assertArrayHasKey('post_tag', $all);
        $this->assertArrayHasKey('post_tone', $all);
        $this->assertArrayHasKey('post_campaign', $all);
        $this->assertArrayHasKey('opm_news_type', $all);
    }

    #[Test]
    public function it_builds_label_options(): void
    {
        $options = TaxonomyRegistry::options();

        $this->assertSame('Category', $options['category']);
        $this->assertSame('Tags', $options['post_tag']);
    }

    #[Test]
    public function it_returns_null_for_missing_taxonomy(): void
    {
        $this->assertNull(TaxonomyRegistry::get('missing'));
    }
}
