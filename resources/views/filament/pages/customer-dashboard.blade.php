<x-filament-panels::page>
    @if ($this->customerType() !== null)
        <div class="mb-4">
            <x-filament::badge :color="$this->customerType()->getColor()" size="lg">
                {{ $this->customerTypeBadgeLabel() }}
            </x-filament::badge>
        </div>
    @endif

    @if ($this->isSezione())
        <div class="mb-4">
            <x-filament::section heading="I miei dati CAI/RUNTS" icon="heroicon-o-identification">
                @include('filament.pages.partials.cai-directory-detail', ['emptyMessage' => 'Nessun dato CAI/RUNTS disponibile per la tua sezione'])
            </x-filament::section>
        </div>
    @endif

    @if ($this->isGruppoRegionale())
        <div class="mb-4">
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
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-filament::section heading="Ticket aperti" icon="heroicon-o-ticket">
            <div class="flex items-center justify-between gap-4">
                @if ($this->openTicketsCount() > 0)
                    <span class="text-3xl font-semibold text-gray-950 dark:text-white">
                        {{ $this->openTicketsCount() }}
                    </span>

                    <a href="{{ $this->openTicketsUrl() }}" class="fi-link text-sm font-medium">
                        Vedi i miei ticket
                    </a>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessun ticket aperto</p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section heading="Richiedono una tua risposta" icon="heroicon-o-chat-bubble-left-right">
            <div class="flex flex-col gap-2">
                @forelse ($this->ticketsAwaitingResponse() as $ticket)
                    <a
                        href="{{ $this->ticketUrl($ticket) }}"
                        class="flex flex-col gap-1 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition hover:border-primary-400 dark:border-white/10 dark:bg-gray-900"
                    >
                        <span class="font-medium text-gray-950 dark:text-white">
                            #{{ $ticket->id }} {{ $ticket->title }}
                        </span>

                        <x-filament::badge :color="$ticket->status->getColor()" size="xs">
                            {{ $ticket->status->getLabel() }}
                        </x-filament::badge>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Nessun ticket in attesa di una tua risposta
                    </p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section heading="Documentazione" icon="heroicon-o-book-open">
            <div class="flex flex-col gap-2">
                @forelse ($this->recentDocumentation() as $page)
                    <div class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $page->title }}
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessuna documentazione disponibile</p>
                @endforelse
            </div>

            @if ($this->recentDocumentation()->isNotEmpty())
                <div class="mt-3">
                    <a href="{{ $this->documentationUrl() }}" class="fi-link text-sm font-medium">
                        Vedi tutta la documentazione
                    </a>
                </div>
            @endif
        </x-filament::section>

        @if ($this->driveUrl() !== null || $this->driveBudgetUrl() !== null)
            <x-filament::section heading="Documenti condivisi" icon="heroicon-o-folder">
                <div class="flex flex-col gap-2 text-sm">
                    @if ($this->driveUrl() !== null)
                        <a href="{{ $this->driveUrl() }}" target="_blank" rel="noopener noreferrer" class="fi-link font-medium">
                            Cartella Drive
                        </a>
                    @endif

                    @if ($this->driveBudgetUrl() !== null)
                        <a href="{{ $this->driveBudgetUrl() }}" target="_blank" rel="noopener noreferrer" class="fi-link font-medium">
                            Budget Drive
                        </a>
                    @endif
                </div>
            </x-filament::section>
        @endif

        <x-filament::section heading="Report attività" icon="heroicon-o-document-chart-bar">
            <div class="flex flex-col gap-2">
                @forelse ($this->ownActivityReports() as $report)
                    <div class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $report->periodLabel() }}
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessun report attività</p>
                @endforelse
            </div>

            @if ($this->ownActivityReports()->isNotEmpty())
                <div class="mt-3">
                    <a href="{{ $this->activityReportsUrl() }}" class="fi-link text-sm font-medium">
                        Vedi tutti i report
                    </a>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section heading="Progetti fundraising" icon="heroicon-o-banknotes">
            <div class="flex flex-col gap-2">
                @forelse ($this->involvedFundraisingProjects() as $project)
                    <a
                        href="{{ $this->fundraisingProjectUrl($project) }}"
                        class="flex flex-col gap-1 rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition hover:border-primary-400 dark:border-white/10 dark:bg-gray-900"
                    >
                        <span class="font-medium text-gray-950 dark:text-white">{{ $project->title }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nessun progetto fundraising</p>
                @endforelse
            </div>

            @if ($this->involvedFundraisingProjects()->isNotEmpty())
                <div class="mt-3">
                    <a href="{{ $this->fundraisingProjectsUrl() }}" class="fi-link text-sm font-medium">
                        Vedi tutti i progetti
                    </a>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
