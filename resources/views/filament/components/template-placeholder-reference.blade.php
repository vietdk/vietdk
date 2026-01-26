@php
    $compact = $compact ?? false;
@endphp

<div class="space-y-4" x-data="{
    search: '',
    copiedToken: null,
    copyToClipboard(token) {
        navigator.clipboard.writeText(token);
        this.copiedToken = token;
        setTimeout(() => this.copiedToken = null, 2000);
    }
}">
    @if (!$compact)
        <div class="mb-4">
            <input
                type="text"
                x-model="search"
                placeholder="Search placeholders..."
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
            />
        </div>
    @endif

    @foreach ($placeholders as $group => $items)
        <div x-show="!search || @js(strtolower($group)).includes(search.toLowerCase()) || {{ json_encode(array_map(fn($item) => strtolower($item[0] . ' ' . $item[1]), $items)) }}.some(item => item.includes(search.toLowerCase()))">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 pb-1 border-b border-gray-200 dark:border-gray-700">
                {{ $group }}
            </div>
            <div class="mt-2 space-y-2">
                @foreach ($items as [$token, $description])
                    <div
                        x-show="!search || @js(strtolower($token . ' ' . $description)).includes(search.toLowerCase())"
                        class="flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 p-2 rounded transition"
                    >
                        <code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 text-gray-800 dark:text-gray-200 font-mono">{{ $token }}</code>
                        @if (!$compact)
                            <span class="flex-1">{{ $description }}</span>
                            <button
                                type="button"
                                @click="copyToClipboard(@js($token))"
                                class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition"
                                x-bind:class="{ 'text-green-600': copiedToken === @js($token) }"
                            >
                                <span x-show="copiedToken !== @js($token)">Copy</span>
                                <span x-show="copiedToken === @js($token)">✓ Copied!</span>
                            </button>
                        @else
                            <button
                                type="button"
                                @click="copyToClipboard(@js($token))"
                                class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 text-xs transition"
                                x-bind:class="{ 'text-green-600': copiedToken === @js($token) }"
                            >
                                <span x-show="copiedToken !== @js($token)">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </span>
                                <span x-show="copiedToken === @js($token)">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
