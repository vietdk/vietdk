<x-filament-panels::page>
    @if($export && $export->output_file_path)
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <x-heroicon-o-document-arrow-down class="w-10 h-10 text-green-600 dark:text-green-400" />
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Export Ready
            </h2>

            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Your bulletin with {{ $export->articles_count }} article(s) is ready for download.
            </p>

            <div class="space-y-4">
                <x-filament::button wire:click="download" size="lg" icon="heroicon-o-arrow-down-tray">
                    Download Bulletin (DOCX)
                </x-filament::button>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Generated on {{ $export->created_at->format('F j, Y \a\t g:i A') }}
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-red-600 dark:text-red-400" />
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Export Not Found
            </h2>

            <p class="text-gray-600 dark:text-gray-400 mb-6">
                The requested export could not be found or has expired.
            </p>

            <x-filament::button tag="a" href="{{ route('filament.admin.pages.export-bulletin') }}">
                Create New Export
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
