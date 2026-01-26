<div x-data="{ activeTab: 'html' }" class="space-y-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm">
                <p class="font-medium text-blue-900 dark:text-blue-100">Preview with Sample Data</p>
                <p class="text-blue-700 dark:text-blue-300 mt-1">
                    Showing preview using {{ $articleCount }} sample article(s).
                    @if($templateType === 'simple')
                        Use the tabs below to switch between HTML (for DOCX export) and plain text views.
                    @else
                        Shortcode templates render as HTML.
                    @endif
                </p>
            </div>
        </div>
    </div>

    @if($templateType === 'simple' && ($htmlPreview || $textPreview))
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex gap-4" aria-label="Tabs">
                @if($htmlPreview)
                    <button
                        @click="activeTab = 'html'"
                        :class="{ 'border-primary-600 text-primary-600': activeTab === 'html', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'html' }"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition"
                    >
                        HTML Preview (DOCX)
                    </button>
                @endif
                @if($textPreview)
                    <button
                        @click="activeTab = 'text'"
                        :class="{ 'border-primary-600 text-primary-600': activeTab === 'text', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'text' }"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition"
                    >
                        Text Preview (TXT)
                    </button>
                @endif
            </nav>
        </div>
    @endif

    @if($htmlPreview)
        <div x-show="activeTab === 'html'" class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">HTML Output</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">This is how it will appear in DOCX files</span>
            </div>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 p-6 max-h-96 overflow-y-auto">
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    {!! $htmlPreview !!}
                </div>
            </div>
        </div>
    @endif

    @if($textPreview && $templateType === 'simple')
        <div x-show="activeTab === 'text'" class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Plain Text Output</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">This is how it will appear in TXT files</span>
            </div>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 p-6 max-h-96 overflow-y-auto">
                <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono">{{ $textPreview }}</pre>
            </div>
        </div>
    @endif

    @if(!$htmlPreview && !$textPreview)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 p-8 text-center">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-400">No template content to preview</p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Add content to your template fields to see a preview</p>
        </div>
    @endif
</div>
