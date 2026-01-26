<?php

namespace Database\Seeders;

use App\Models\ExportTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShortcodeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('export_templates')->delete();
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE export_templates AUTO_INCREMENT = 1');
        }

        $templates = [
            [
                'name' => 'Australia - Vietnam Daily News Report',
                'description' => 'Shortcode template based on the Australia daily bulletin.',
                'shortcode_body' => $this->readTemplate('aussie template parser.txt'),
                'is_default' => true,
            ],
            [
                'name' => 'Vietnam News Briefs',
                'description' => 'Shortcode template based on the NBS news briefs format.',
                'shortcode_body' => $this->readTemplate('nbs template parser.txt'),
                'is_default' => false,
            ],
        ];

        foreach ($templates as $template) {
            ExportTemplate::create([
                'name' => $template['name'],
                'description' => $template['description'],
                'template_type' => 'shortcode',
                'shortcode_body' => $template['shortcode_body'],
                'file_path' => 'inline',
                'filters' => [],
                'is_default' => $template['is_default'],
                'grouping_type' => 'none',
                'grouping_order' => [],
                'show_group_headers' => true,
                'group_header_format' => '=== {{group_name}} ===',
            ]);
        }
    }

    protected function readTemplate(string $filename): string
    {
        $path = base_path('resources/bulletins/temp parser/' . $filename);
        if (!file_exists($path)) {
            return '';
        }

        return file_get_contents($path) ?: '';
    }
}
