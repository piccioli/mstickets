<x-filament-panels::page>
    <x-filament::section heading="Sezioni del gruppo regionale" icon="heroicon-o-map">
        <div class="flex flex-col gap-2">
            @forelse ($this->regionalGroupSections() as $section)
                <a
                    href="{{ $this->sectionDetailUrl($section) }}"
                    class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition hover:border-primary-400 dark:border-white/10 dark:bg-gray-900"
                >
                    <span class="font-medium text-gray-950 dark:text-white">{{ $section->name }}</span>

                    <span class="text-gray-500 dark:text-gray-400">
                        {{ $this->sectionOpenTicketsCount($section) }} ticket aperti
                    </span>
                </a>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nessuna sezione classificata in questa regione
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
