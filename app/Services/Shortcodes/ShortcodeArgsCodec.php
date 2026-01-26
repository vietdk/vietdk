<?php

namespace App\Services\Shortcodes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ShortcodeArgsCodec
{
    public function encode(array $args): string
    {
        $normalized = $this->normalize($args);

        return base64_encode(json_encode($normalized, JSON_UNESCAPED_SLASHES));
    }

    public function decode(string $payload): array
    {
        try {
            $decoded = base64_decode($payload, true);
            if ($decoded === false) {
                if (config('templates.rendering.enable_logging', false)) {
                    Log::warning('Base64 decode failed for shortcode args', [
                        'payload_length' => strlen($payload),
                    ]);
                }
                return [];
            }

            $json = json_decode($decoded, true);
            if (!is_array($json)) {
                if (config('templates.rendering.enable_logging', false)) {
                    Log::warning('JSON decode failed for shortcode args', [
                        'error' => json_last_error_msg(),
                        'decoded_length' => strlen($decoded),
                    ]);
                }
                return [];
            }

            return $this->normalize($json);
        } catch (\Exception $e) {
            Log::error('Shortcode args decode exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function normalize(array $args): array
    {
        $taxQuery = Arr::get($args, 'tax_query', []);
        if (isset($taxQuery['relation'])) {
            $relation = strtoupper((string) $taxQuery['relation']);
        } else {
            $relation = strtoupper((string) Arr::get($args, 'relation', 'AND'));
        }

        $normalized = [
            'tax_query' => [
                'relation' => in_array($relation, ['AND', 'OR'], true) ? $relation : 'AND',
            ],
            'fields' => Arr::get($args, 'fields', 'ids'),
        ];

        foreach ($taxQuery as $key => $filter) {
            if ($key === 'relation' || !is_array($filter)) {
                continue;
            }

            $normalized['tax_query'][] = [
                'taxonomy' => Arr::get($filter, 'taxonomy'),
                'field' => Arr::get($filter, 'field', 'id'),
                'operator' => strtoupper((string) Arr::get($filter, 'operator', 'IN')),
                'terms' => array_values(array_filter((array) Arr::get($filter, 'terms', []))),
                'children' => Arr::get($filter, 'children', 'true') ? 'true' : 'false',
            ];
        }

        return $normalized;
    }
}
