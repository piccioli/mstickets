@php
    use App\Domain\Ticketing\Enums\TicketStatus;
@endphp

<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-3">
        <label for="work-board-assignee" class="text-sm font-medium text-gray-950 dark:text-white">
            Board di
        </label>

        <x-filament::input.wrapper class="max-w-xs">
            <x-filament::input.select id="work-board-assignee" wire:model.live="assigneeId">
                <option value="">Tutti</option>
                @foreach ($this->assigneeOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-2">
        @foreach ($this->columns() as $statusValue => $tickets)
            @php $status = TicketStatus::from($statusValue); @endphp

            <div class="flex w-72 shrink-0 flex-col gap-3 rounded-xl bg-gray-50 p-3 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <x-filament::badge :color="$status->getColor()" :icon="$status->getIcon()">
                        {{ $status->getLabel() }}
                    </x-filament::badge>

                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ $tickets->count() }}
                    </span>
                </div>

                <div class="flex flex-col gap-2">
                    @forelse ($tickets as $ticket)
                        <a
                            href="{{ $this->ticketUrl($ticket) }}"
                            class="flex flex-col gap-1 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition hover:border-primary-400 dark:border-white/10 dark:bg-gray-900"
                        >
                            <span class="font-medium text-gray-950 dark:text-white">
                                #{{ $ticket->id }} {{ $ticket->title }}
                            </span>

                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->clientName($ticket) }}
                            </span>

                            @if ($ticket->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($ticket->tags as $tag)
                                        <x-filament::badge color="gray" size="xs">{{ $tag->name }}</x-filament::badge>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <x-filament::badge :color="$ticket->priority->getColor()">
                                    {{ $ticket->priority->getLabel() }}
                                </x-filament::badge>

                                <span>{{ $ticket->status_changed_at->diffForHumans() }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-xs text-gray-400 dark:text-gray-500">Nessun ticket</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <x-filament::section heading="Attività recenti" icon="heroicon-o-clock">
        <div class="flex flex-col divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($this->recentActivity() as $log)
                <div class="flex items-center justify-between gap-4 py-2 text-sm">
                    <div class="flex flex-col">
                        <a href="{{ $this->ticketUrl($log->ticket) }}" class="fi-link font-medium">
                            #{{ $log->ticket->id }} {{ $log->ticket->title }}
                        </a>

                        <span class="text-gray-500 dark:text-gray-400">{{ $this->describeLog($log) }}</span>
                    </div>

                    <div class="flex flex-col items-end text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $log->user?->name ?? 'Sistema' }}</span>
                        <span>{{ $log->occurred_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="py-2 text-sm text-gray-400 dark:text-gray-500">Nessuna attività recente</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
