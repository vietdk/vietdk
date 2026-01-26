<div class="space-y-4" x-data="{
    search: '',
    copiedToken: null,
    copyToClipboard(token) {
        navigator.clipboard.writeText(token);
        this.copiedToken = token;
        setTimeout(() => this.copiedToken = null, 2000);
    }
}">
    <div class="mb-4">
        <input
            type="text"
            x-model="search"
            placeholder="Search placeholders..."
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
        />
        <p class="mt-1 text-xs text-gray-500">Tip: Click any placeholder to copy it to your clipboard</p>
    </div>

    @foreach ($placeholders as $group => $items)
        <div x-show="!search || @js(strtolower($group)).includes(search.toLowerCase()) || {{ json_encode(array_map(fn($item) => strtolower($item[0] . ' ' . $item[1]), $items)) }}.some(item => item.includes(search.toLowerCase()))">
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 pb-1 border-b border-gray-200 dark:border-gray-700">
                {{ $group }}
            </div>
            <div class="mt-2 space-y-2">
                @foreach ($items as [$token, $description])
                    <div
                        x-show="!search || @js(strtolower($token . ' ' . $description)).includes(search.toLowerCase())"
                        class="flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 p-2 rounded transition cursor-pointer"
                        @click="copyToClipboard(@js($token))"
                    >
                        <code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 text-gray-800 dark:text-gray-200 font-mono">{{ $token }}</code>
                        <span class="flex-1">{{ $description }}</span>
                        <span
                            class="text-primary-600 dark:text-primary-400 font-medium transition"
                            x-bind:class="{ 'text-green-600': copiedToken === @js($token) }"
                        >
                            <span x-show="copiedToken !== @js($token)">Click to copy</span>
                            <span x-show="copiedToken === @js($token)" class="text-green-600 dark:text-green-400">✓ Copied!</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
