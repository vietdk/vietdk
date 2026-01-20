<div class="space-y-4">
    @if($articles->isEmpty())
        <div class="text-gray-500 dark:text-gray-400 text-center py-8">
            <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>No articles match your current filters.</p>
            <p class="text-sm">Adjust the filters above to include articles.</p>
        </div>
    @else
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Showing {{ $articles->count() }} article(s) that will be included in the bulletin:
        </div>

        <div class="space-y-3">
            @foreach($articles as $index => $article)
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <span class="flex-shrink-0 w-6 h-6 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full flex items-center justify-center text-xs font-medium">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ $article->title }}
                        </h4>
                        <div class="flex flex-wrap gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @if($article->category)
                                <span class="inline-flex items-center px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded">
                                    {{ $article->category->name }}
                                </span>
                            @endif
                            @if($article->published_at)
                                <span>{{ $article->published_at->format('M j, Y') }}</span>
                            @endif
                            <span>by {{ $article->author->name ?? 'Unknown' }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($articles->count() >= 10)
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                Preview limited to 10 articles. More may be included in export.
            </p>
        @endif
    @endif
</div>
