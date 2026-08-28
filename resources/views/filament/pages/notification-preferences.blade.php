<x-filament-panels::page>
    <x-filament::section heading="Comunicazioni email" icon="heroicon-o-bell-alert">
        <div class="flex flex-col gap-3">
            @forelse ($this->applicableTypes() as $type)
                <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $type->getLabel() }}
                    </span>

                    <x-filament::input.checkbox wire:model.live="enabled.{{ $type->value }}" />
                </label>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nessuna preferenza disponibile per il tuo ruolo.
                </p>
            @endforelse
        </div>

        @if (! empty($this->applicableTypes()))
            <div class="mt-4">
                <x-filament::button wire:click="save">
                    Salva preferenze
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
