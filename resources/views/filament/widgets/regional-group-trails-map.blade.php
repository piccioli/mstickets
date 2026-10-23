{{-- Mappa per settori di "Sentieri del GR" (US-1065), widget App\Filament\Widgets\RegionalGroupTrailsMap: sempre resa,
     anche senza regione o senza settori (areas vuote, lines-url null), mai dentro un @if. --}}
<div class="ms-gr-trails__map-card">
    <x-cai.trail-sectors-map :name="$name" :areas="$areas" :lines-url="$linesUrl" />
</div>
