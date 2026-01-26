<?php

namespace App\Filament\Resources\ExportTemplateResource\Pages;

use App\Filament\Resources\ExportTemplateResource;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Exporter\TemplateRenderer;
use App\Services\Shortcodes\ShortcodeTemplateRenderer;
use App\Services\TemplateValidator;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class EditExportTemplate extends EditRecord
{
    protected static string $resource = ExportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview_template')
                ->label('Preview Template')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Template Preview')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('7xl')
                ->modalContent(function () {
                    $template = $this->record;
                    $articles = $this->getSampleArticles();

                    // Render HTML version
                    $htmlBody = $template->template_type === 'shortcode'
                        ? $template->shortcode_body ?? ''
                        : $template->html_body ?? '';

                    $htmlPreview = '';
                    if ($htmlBody) {
                        if ($template->template_type === 'shortcode') {
                            $renderer = new ShortcodeTemplateRenderer();
                            $htmlPreview = $renderer->render(
                                $htmlBody,
                                Article::query()
                                    ->with(['category', 'tags', 'tone', 'campaign', 'sourceMetadata'])
                                    ->whereIn('id', $articles->pluck('id')),
                                [
                                    'export_date' => now(),
                                    'total_articles' => $articles->count(),
                                ]
                            );
                        } else {
                            $renderer = new TemplateRenderer();
                            $htmlPreview = $renderer->render(
                                $htmlBody,
                                $articles,
                                [
                                    'export_date' => now(),
                                    'total_articles' => $articles->count(),
                                ],
                                false
                            );
                        }
                    }

                    // Render text version
                    $textBody = $template->template_type === 'simple'
                        ? $template->text_body ?? ''
                        : '';

                    $textPreview = '';
                    if ($textBody && $template->template_type === 'simple') {
                        $renderer = new TemplateRenderer();
                        $textPreview = $renderer->render(
                            $textBody,
                            $articles,
                            [
                                'export_date' => now(),
                                'total_articles' => $articles->count(),
                            ],
                            true
                        );
                    }

                    return view('filament.components.template-preview', [
                        'htmlPreview' => $htmlPreview,
                        'textPreview' => $textPreview,
                        'templateType' => $template->template_type,
                        'articleCount' => $articles->count(),
                    ]);
                }),

            Actions\Action::make('test_export')
                ->label('Download Test Export')
                ->icon('heroicon-o-beaker')
                ->form([
                    Select::make('output_format')
                        ->label('Output Format')
                        ->options([
                            'docx' => 'DOCX',
                            'txt' => 'TXT',
                        ])
                        ->default('docx')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $template = $this->record;
                    $outputFormat = $data['output_format'] ?? 'docx';
                    if ($template->template_type === 'shortcode') {
                        $body = $template->shortcode_body ?? '';
                    } else {
                        $body = $outputFormat === 'txt' ? ($template->text_body ?? '') : ($template->html_body ?? '');
                    }

                    if ($template->template_type !== 'shortcode') {
                        $validator = new TemplateValidator();
                        $warnings = $validator->validate($body, $outputFormat === 'docx');

                        if (!empty($warnings)) {
                            Notification::make()
                                ->title('Template warnings')
                                ->body(implode("\n", $warnings))
                                ->warning()
                                ->send();
                        }
                    }

                    $articles = $this->getSampleArticles();
                    if ($template->template_type === 'shortcode') {
                        $renderer = new ShortcodeTemplateRenderer();
                        $rendered = $renderer->render(
                            $body,
                            Article::query()
                                ->with(['category', 'tags', 'tone', 'campaign', 'sourceMetadata'])
                                ->whereIn('id', $articles->pluck('id')),
                            [
                                'export_date' => now(),
                                'total_articles' => $articles->count(),
                            ]
                        );
                        if ($outputFormat === 'txt') {
                            $rendered = trim(strip_tags(str_ireplace(['<br>', '<br />', '</p>'], "\n", $rendered)));
                        }
                    } else {
                        $renderer = new TemplateRenderer();
                        $rendered = $renderer->render(
                            $body,
                            $articles,
                            [
                                'export_date' => now(),
                                'total_articles' => $articles->count(),
                            ],
                            $outputFormat === 'txt'
                        );
                    }

                    Storage::disk('local')->makeDirectory('exports');
                    $filename = 'template_test_' . now()->format('Y-m-d_His');

                    if ($outputFormat === 'txt') {
                        $path = 'exports/' . $filename . '.txt';
                        Storage::disk('local')->put($path, $rendered);
                        return response()->download(Storage::disk('local')->path($path))->deleteFileAfterSend(true);
                    }

                    $phpWord = new PhpWord();
                    $section = $phpWord->addSection();
                    if ($rendered !== '') {
                        Html::addHtml($section, $rendered, false, false);
                    }

                    $path = 'exports/' . $filename . '.docx';
                    $fullPath = Storage::disk('local')->path($path);
                    $writer = IOFactory::createWriter($phpWord, 'Word2007');
                    $writer->save($fullPath);

                    return response()->download($fullPath)->deleteFileAfterSend(true);
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSampleArticles(): Collection
    {
        $sample = Article::query()
            ->with(['category', 'author', 'tags', 'sourceMetadata'])
            ->latest('approved_at')
            ->limit(5)
            ->get();

        if ($sample->isNotEmpty()) {
            return $sample;
        }

        return collect([
            $this->makeSampleArticle(
                'Sample Energy Update',
                'Energy',
                'Sample paragraph one.\n\nSample paragraph two.',
                ['Energy', 'Policy']
            ),
            $this->makeSampleArticle(
                'Sample Market Brief',
                'Markets',
                'Sample market overview for testing templates.',
                ['Markets', 'Vietnam']
            ),
        ]);
    }

    protected function makeSampleArticle(string $title, string $categoryName, string $body, array $tags): Article
    {
        $article = new Article([
            'title' => $title,
            'body' => $body,
            'excerpt' => Str::limit(strip_tags($body), 120),
            'approved_at' => now(),
            'published_at' => now(),
            'status' => Article::STATUS_APPROVED,
        ]);

        $article->setRelation('category', new Category(['name' => $categoryName]));
        $article->setRelation('author', new User(['name' => 'Sample Editor']));
        $article->setRelation('tags', collect($tags)->map(fn ($tag) => new Tag(['name' => $tag])));

        return $article;
    }
}
