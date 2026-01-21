<x-filament-panels::page>
    <form wire:submit="export">
        {{ $this->form }}

        <div class="mt-6 flex gap-4">
            <x-filament::button type="submit" size="lg">
                Export Bulletin ({{ strtoupper($this->data['output_format'] ?? 'DOCX') }})
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="$refresh">
                Refresh Preview
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
