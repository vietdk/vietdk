<?php

namespace Tests\Unit\Shortcodes;

use App\Services\Shortcodes\ShortcodeArgsCodec;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShortcodeArgsCodecTest extends TestCase
{
    #[Test]
    public function it_encodes_and_decodes_args(): void
    {
        $codec = new ShortcodeArgsCodec();
        $args = [
            'fields' => 'ids',
            'tax_query' => [
                'relation' => 'or',
                [
                    'taxonomy' => 'category',
                    'field' => 'id',
                    'operator' => 'in',
                    'terms' => [1, 2],
                    'children' => true,
                ],
            ],
        ];

        $encoded = $codec->encode($args);
        $decoded = $codec->decode($encoded);

        $this->assertSame('OR', $decoded['tax_query']['relation']);
        $this->assertSame('ids', $decoded['fields']);
        $this->assertSame('category', $decoded['tax_query'][0]['taxonomy']);
        $this->assertSame('IN', $decoded['tax_query'][0]['operator']);
        $this->assertSame(['1', '2'], array_map('strval', $decoded['tax_query'][0]['terms']));
        $this->assertSame('true', $decoded['tax_query'][0]['children']);
    }

    #[Test]
    public function it_returns_empty_array_for_invalid_payload(): void
    {
        $codec = new ShortcodeArgsCodec();

        $this->assertSame([], $codec->decode('not-base64'));
    }
}
