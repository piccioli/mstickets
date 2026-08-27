@php
    $sal = $record->sal();
@endphp

@if ($sal === null)
    <span class="text-xs text-gray-400">—</span>
@else
    @php
        $clamped = max(0.0, min(100.0, $sal));
        $color = match (true) {
            $sal > 100.0 => \App\Support\DesignTokens::get('ms-action-danger'),
            $sal >= 80.0 => \App\Support\DesignTokens::get('ms-action-warning-cta'),
            default => \App\Support\DesignTokens::get('ms-success-dot'),
        };
    @endphp

    <div class="flex items-center gap-2">
        <div class="h-2 w-24 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
            <div class="h-full rounded-full" style="width: {{ $clamped }}%; background-color: {{ $color }}"></div>
        </div>
        <span class="text-xs">{{ number_format($sal, 0) }}%</span>
    </div>
@endif
