<?php

namespace Database\Seeders;

use App\Models\ExportTemplate;
use Illuminate\Database\Seeder;

class SampleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Pack 1 - Vietnam News Brief Service',
                'description' => 'Tagged format with [SO], [DD], [HH], [QQ] blocks.',
                'template_type' => 'simple',
                'html_body' => null,
                'text_body' => "[SO] {{source_name|Vietnam News Brief Service}}\n[DD] {{export_date|dd : mm : yyyy}}\n\n{{#articles}}\n[HH] {{category}}: {{title}}\n{{#body_paragraphs}}\n[QQ] {{paragraph}}[QQ]\n{{/body_paragraphs}}\n\n{{/articles}}",
                'grouping_type' => 'none',
            ],
            [
                'name' => 'AES Mong Duong - Energy Bulletin',
                'description' => 'Energy bulletin table layout for AES Mong Duong.',
                'template_type' => 'simple',
                'html_body' => "<h1>Vietnam Energy Bulletin</h1>\n<p>{{export_date|M d, Y}}</p>\n<table border=\"1\" style=\"width:100%; border-collapse:collapse;\">\n<tr style=\"background:#f2f2f2;\">\n<th>Date</th>\n<th>Headlines</th>\n<th>Media Title</th>\n<th>Media Type</th>\n<th>Tone</th>\n</tr>\n{{#group_by_category}}\n<tr>\n<td colspan=\"5\"><strong>{{category_name}}</strong></td>\n</tr>\n{{#articles}}\n<tr>\n<td>{{approved_date|M d}}</td>\n<td><strong>{{title}}</strong><br />{{excerpt}}</td>\n<td>{{source}}</td>\n<td>{{tags}}</td>\n<td>{{tone}}</td>\n</tr>\n{{/articles}}\n{{/group_by_category}}\n</table>",
                'text_body' => null,
                'grouping_type' => 'category',
            ],
            [
                'name' => 'GIZ Energy Daily',
                'description' => 'Energy daily with table of contents and content sections.',
                'template_type' => 'simple',
                'html_body' => "<h1>Vietnam Energy Bulletin</h1>\n<p>{{export_date|M d, Y}}</p>\n<h2>TABLE OF CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n<ul>\n{{#articles}}\n<li>{{title}}</li>\n{{/articles}}\n</ul>\n{{/group_by_category}}\n<h2>CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n{{#articles}}\n<h4>{{title}}</h4>\n<p>{{body_excerpt|600}}</p>\n<p><em>Source: {{source}}</em></p>\n<p>[Back to top]</p>\n{{/articles}}\n{{/group_by_category}}",
                'text_body' => null,
                'grouping_type' => 'category',
            ],
            [
                'name' => 'Vietnam Weekly Digest',
                'description' => 'Weekly digest with TOC and content grouped by category.',
                'template_type' => 'simple',
                'html_body' => "<p><strong>TOAN VIET COMPANY LIMITED</strong></p>\n<p>Address: 502, No.27 Lane 115 Nui Truc Str, Ba Dinh Dist, Hanoi</p>\n<p>Telephone: (+84 24) 3772 0378</p>\n<p>Email: news@vietpan.com</p>\n<p>Website: www.vietpan.com</p>\n<h1>Vietnam Weekly Digest</h1>\n<p>{{export_date|M d, Y}}</p>\n<h2>TABLE OF CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n<ul>\n{{#articles}}\n<li>{{title}}</li>\n{{/articles}}\n</ul>\n{{/group_by_category}}\n<h2>CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n{{#articles}}\n<h4>{{title}}</h4>\n<p>{{body_excerpt|600}}</p>\n<p><em>{{source}}</em></p>\n{{/articles}}\n{{/group_by_category}}",
                'text_body' => null,
                'grouping_type' => 'category',
            ],
            [
                'name' => 'Daily News Report',
                'description' => 'Australia - Vietnam Daily News Report table layout.',
                'template_type' => 'simple',
                'html_body' => "<h1>Australia - Vietnam Daily News Report</h1>\n<p>{{export_date|M d, Y}}</p>\n<p>This newsletter provides briefings of stories about Australia and other key topics in the major Vietnam media outlets. For full briefings, click \"Go to full brief\" in the attached PDF file to read the complete briefing.</p>\n<table border=\"1\" style=\"width:100%; border-collapse:collapse;\">\n<tr style=\"background:#f2f2f2;\">\n<th>Date</th>\n<th>News</th>\n<th>Tone</th>\n<th>Media Outlets</th>\n</tr>\n{{#group_by_category}}\n<tr>\n<td colspan=\"4\"><strong>{{category_name}}</strong></td>\n</tr>\n{{#articles}}\n<tr>\n<td>{{approved_date|M d}}</td>\n<td><strong>{{title}}</strong><br />{{excerpt}}<br /><em>Go to full brief</em></td>\n<td>{{tone}}</td>\n<td>{{source}}</td>\n</tr>\n{{/articles}}\n{{/group_by_category}}\n</table>",
                'text_body' => null,
                'grouping_type' => 'category',
            ],
            [
                'name' => 'NBS - Vietnam News Briefs',
                'description' => 'News briefs with category TOC and detailed content.',
                'template_type' => 'simple',
                'html_body' => "<p><strong>TOAN VIET COMPANY LIMITED</strong></p>\n<p>Address: 502, No.27 Lane 115 Nui Truc Str, Ba Dinh Dist, Hanoi</p>\n<p>Telephone: (+84 24) 3772 0378</p>\n<p>Email: news@vietpan.com</p>\n<p>Website: www.vietpan.com</p>\n<h1>Vietnam News Briefs</h1>\n<p>{{export_date|F d, Y}}</p>\n<p>Total articles: {{total_articles}} articles</p>\n<h2>TABLE OF CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n<ul>\n{{#articles}}\n<li>{{title}}</li>\n{{/articles}}\n</ul>\n{{/group_by_category}}\n<h2>CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n{{#articles}}\n<h4>{{title}}</h4>\n<p>{{body_excerpt|600}}</p>\n<p><em>{{source}}</em></p>\n<p>[Back to top]</p>\n{{/articles}}\n{{/group_by_category}}",
                'text_body' => null,
                'grouping_type' => 'category',
            ],
            [
                'name' => 'Zarubezhneft Clipping',
                'description' => 'Press clipping with sections and full content.',
                'template_type' => 'simple',
                'html_body' => "<h1>Press Clipping for Zarubezhneft Vietnam</h1>\n<p>{{export_date|F d, Y}}</p>\n<h2>TABLE OF CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n<ul>\n{{#articles}}\n<li>{{title}}</li>\n{{/articles}}\n</ul>\n{{/group_by_category}}\n<h2>CONTENT</h2>\n{{#group_by_category}}\n<h3>{{category_name}}</h3>\n{{#articles}}\n<h4>{{title}}</h4>\n<p>{{body_excerpt|600}}</p>\n<p><em>{{source}}</em></p>\n<p>[Back to top]</p>\n{{/articles}}\n{{/group_by_category}}",
                'text_body' => null,
                'grouping_type' => 'category',
            ],
        ];

        foreach ($templates as $template) {
            ExportTemplate::updateOrCreate(
                ['name' => $template['name']],
                array_merge($template, [
                    'file_path' => 'inline',
                    'filters' => $template['filters'] ?? [],
                    'is_default' => false,
                    'grouping_order' => $template['grouping_order'] ?? [],
                    'show_group_headers' => $template['show_group_headers'] ?? true,
                    'group_header_format' => $template['group_header_format'] ?? '=== {{group_name}} ===',
                ])
            );
        }
    }
}
