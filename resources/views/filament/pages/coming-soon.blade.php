<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-clock">
        <p>{{ $this->message() }}</p>

        <p class="mt-4">
            <x-filament::link :href="$this->dashboardUrl()" icon="heroicon-m-arrow-left">
                Torna alla dashboard
            </x-filament::link>
        </p>
    </x-filament::section>
</x-filament-panels::page>
