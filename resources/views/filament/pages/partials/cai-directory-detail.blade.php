{{--
    Contenuto CAI/RUNTS di una singola sezione (US-806/US-807): riusato tal quale sia dalla
    dashboard cliente Sezione ({{ $this->caiSection() }} = la propria) sia dalla pagina di
    dettaglio aperta da un cliente Gruppo Regionale ({{ $this->caiSection() }} = la sezione
    scelta dalla card "Sezioni del gruppo regionale") — entrambe le pagine espongono gli stessi
    metodi `caiSection()`/`caiSubsection()`/`caiSectionInfolist` (design doc Fase 8 §7: nessuna
    duplicazione di markup/logica fra i punti di accesso). `$emptyMessage` è l'unica variazione
    ammessa fra i due contesti.
--}}
@if ($this->caiSection() !== null)
    {{ $this->caiSectionInfolist }}
@elseif ($this->caiSubsection() !== null)
    <div class="grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
        <div><span class="font-medium text-gray-950 dark:text-white">Denominazione:</span> {{ $this->caiSubsection()->name }}</div>
        <div><span class="font-medium text-gray-950 dark:text-white">Sezione di riferimento:</span> {{ $this->caiSubsection()->section?->name ?? '—' }}</div>
        <div><span class="font-medium text-gray-950 dark:text-white">Email:</span> {{ $this->caiSubsection()->email ?? '—' }}</div>
        <div><span class="font-medium text-gray-950 dark:text-white">Telefono:</span> {{ $this->caiSubsection()->phone ?? '—' }}</div>
        <div><span class="font-medium text-gray-950 dark:text-white">Indirizzo:</span> {{ $this->caiSubsection()->address ?? '—' }}</div>
        <div><span class="font-medium text-gray-950 dark:text-white">Anno di fondazione:</span> {{ $this->caiSubsection()->founded_year ?? '—' }}</div>
        <div><span class="font-medium text-gray-950 dark:text-white">Numero soci:</span> {{ $this->caiSubsection()->members_count ?? '—' }}</div>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $emptyMessage }}
    </p>
@endif
