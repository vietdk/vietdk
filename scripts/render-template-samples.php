<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\CrawledMetadata;
use App\Models\ExportTemplate;
use App\Models\NewsSource;
use App\Models\Tag;
use App\Models\Tone;
use App\Models\User;
use App\Services\Exporter\TemplateRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$outputDir = storage_path('app/exports/template-tests');
$htmlOutputDir = storage_path('app/exports/template-tests/html');
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}
if (!is_dir($htmlOutputDir)) {
    mkdir($htmlOutputDir, 0775, true);
}

$targetNames = [
    'Pack 1 - Vietnam News Brief Service',
    'AES Mong Duong - Energy Bulletin',
    'GIZ Energy Daily',
    'Vietnam Weekly Digest',
    'Daily News Report',
    'NBS - Vietnam News Briefs',
    'Zarubezhneft Clipping',
];

$templates = ExportTemplate::query()->whereIn('name', $targetNames)->get();
if ($templates->isEmpty()) {
    fwrite(STDOUT, "No templates found. Run the SampleTemplateSeeder first.\n");
    exit(1);
}

$articles = Article::query()->with(['category', 'author', 'tags', 'tone', 'sourceMetadata.newsSource'])->limit(3)->get();
if ($articles->isEmpty()) {
    $articles = collect([
        buildSampleArticle(
            'AMRO Raises ASEAN+3 Growth Outlook',
            'Economic Indicator',
            'AMRO raised its growth forecast and expects Vietnam to post the region\'s fastest expansion in 2026.',
            'Vietnam News Agency',
            'Neutral',
            ['Economy', 'ASEAN']
        ),
        buildSampleArticle(
            'Vietnam Credit May Grow 18% in 2026',
            'Banking',
            'Analysts expect credit growth to accelerate with infrastructure and housing demand supporting lending.',
            'Bao Tin Tuc',
            'Neutral',
            ['Finance']
        ),
        buildSampleArticle(
            'Vietnam Electricity Targets May 2026 Commercial Operation',
            'Power',
            'Vietnam Electricity plans commercial operation for a major power plant in May 2026.',
            'Nguoi Quan Sat',
            'Neutral',
            ['Energy', 'Power']
        ),
    ]);
}

$renderer = new TemplateRenderer();

foreach ($templates as $template) {
    $slug = Str::slug($template->name);
    $context = [
        'export_date' => now(),
        'total_articles' => $articles->count(),
        'show_group_headers' => $template->show_group_headers,
        'group_header_format' => $template->group_header_format,
    ];

    if (!empty($template->text_body)) {
        $text = $renderer->render($template->text_body, $articles, $context, true);
        $path = $outputDir . DIRECTORY_SEPARATOR . $slug . '.txt';
        file_put_contents($path, $text);
        fwrite(STDOUT, "Generated TXT: {$path}\n");
        continue;
    }

    if (!empty($template->html_body)) {
        $html = $renderer->render($template->html_body, $articles, $context, false);
        $rawPath = $htmlOutputDir . DIRECTORY_SEPARATOR . $slug . '.raw.html';
        file_put_contents($rawPath, $html);
        $html = sanitizeHtml($html);
        $sanitizedPath = $htmlOutputDir . DIRECTORY_SEPARATOR . $slug . '.sanitized.html';
        file_put_contents($sanitizedPath, $html);
        $html = normalizeHtml($html);
        $htmlPath = $htmlOutputDir . DIRECTORY_SEPARATOR . $slug . '.html';
        file_put_contents($htmlPath, $html);
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        if ($html !== '') {
            Html::addHtml($section, $html, false, false);
        }
        $path = $outputDir . DIRECTORY_SEPARATOR . $slug . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        fwrite(STDOUT, "Generated DOCX: {$path}\n");
    }
}

function buildSampleArticle(string $title, string $categoryName, string $body, string $sourceName, string $toneName, array $tags): Article
{
    $article = new Article([
        'title' => $title,
        'body' => $body,
        'excerpt' => Str::limit(strip_tags($body), 180),
        'approved_at' => now(),
        'published_at' => now(),
        'status' => Article::STATUS_APPROVED,
    ]);

    $article->setRelation('category', new Category(['name' => $categoryName]));
    $article->setRelation('author', new User(['name' => 'Sample Editor']));
    $article->setRelation('tone', new Tone(['name' => $toneName]));
    $article->setRelation('tags', collect($tags)->map(fn ($tag) => new Tag(['name' => $tag])));

    $newsSource = new NewsSource(['name' => $sourceName]);
    $metadata = new CrawledMetadata(['url' => 'https://example.com/source']);
    $metadata->setRelation('newsSource', $newsSource);
    $article->setRelation('sourceMetadata', $metadata);

    return $article;
}

function sanitizeHtml(string $html): string
{
    if ($html === '') {
        return $html;
    }

    $html = preg_replace('/<\/?(thead|tbody|tfoot|colgroup)[^>]*>/i', '', $html) ?? $html;
    $html = str_ireplace(['<th', '</th>'], ['<td', '</td>'], $html);
    return str_ireplace(['<br>', '<hr>'], ['<br />', '<hr />'], $html);
}

function normalizeHtml(string $html): string
{
    if ($html === '') {
        return $html;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="UTF-8"?><html><body>' . $html . '</body></html>');
    $body = $dom->getElementsByTagName('body')->item(0);

    $normalized = '';
    if ($body) {
        foreach ($body->childNodes as $child) {
            $normalized .= $dom->saveXML($child);
        }
    }

    libxml_clear_errors();

    return $normalized ?? $html;
}
