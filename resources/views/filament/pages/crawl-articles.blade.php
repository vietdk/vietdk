<x-filament-panels::page>
    <form wire:submit="crawl">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Crawl Now
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
