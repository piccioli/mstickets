<?php

declare(strict_types=1);

use App\Import\Validation\WorkedHoursDeviationAnalyzer;

it('conta come confrontabili solo i ticket con ore v1 positive', function (): void {
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 1, 'v1_hours' => null, 'v2_minutes' => 120],
        ['id' => 2, 'v1_hours' => 0.0, 'v2_minutes' => 0],
        ['id' => 3, 'v1_hours' => 10.0, 'v2_minutes' => 600],
    ]);

    expect($analysis['skipped_no_v1_hours'])->toBe(2)
        ->and($analysis['compared'])->toBe(1);
});

it('classifica un ticket entro la tolleranza del 5%', function (): void {
    // 10 ore v1, 10h06 in v2 → scostamento 1%, entro tolleranza.
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 1, 'v1_hours' => 10.0, 'v2_minutes' => 606],
    ]);

    expect($analysis['within_tolerance'])->toBe(1)
        ->and($analysis['beyond_tolerance'])->toBe([]);
});

it('elenca un ticket oltre la tolleranza del 5% con lo scostamento percentuale', function (): void {
    // 10 ore v1, 12 ore v2 → scostamento 20%, oltre tolleranza.
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 42, 'v1_hours' => 10.0, 'v2_minutes' => 12 * 60],
    ]);

    expect($analysis['within_tolerance'])->toBe(0)
        ->and($analysis['beyond_tolerance'])->toHaveCount(1)
        ->and($analysis['beyond_tolerance'][0]['id'])->toBe(42)
        ->and($analysis['beyond_tolerance'][0]['deviation_percent'])->toBe(20.0);
});

it('calcola la distribuzione min/media/max degli scostamenti su più ticket', function (): void {
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 1, 'v1_hours' => 10.0, 'v2_minutes' => 10 * 60], // 0%
        ['id' => 2, 'v1_hours' => 10.0, 'v2_minutes' => 11 * 60], // 10%
    ]);

    expect($analysis['min_deviation_percent'])->toBe(0.0)
        ->and($analysis['max_deviation_percent'])->toBe(10.0)
        ->and($analysis['avg_deviation_percent'])->toBe(5.0);
});

it('usa una tolleranza personalizzata quando indicata', function (): void {
    $analysis = WorkedHoursDeviationAnalyzer::analyze(
        [['id' => 1, 'v1_hours' => 10.0, 'v2_minutes' => 11 * 60]], // 10%
        tolerance: 0.2,
    );

    expect($analysis['within_tolerance'])->toBe(1)
        ->and($analysis['beyond_tolerance'])->toBe([]);
});

it('considera entro tolleranza un ticket con scostamento percentuale enorme ma differenza assoluta minima', function (): void {
    // Bug reale scoperto sul dump v1 (US-219): un ticket con 0.17h in v1 (~10
    // minuti) e 0h in v2 (arrotondato alla granularità di 10') risulta "100% di
    // scostamento" con la sola tolleranza percentuale, ma la differenza reale è
    // di pochi minuti — rumore di arrotondamento, non un problema di dominio.
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 1, 'v1_hours' => 0.17, 'v2_minutes' => 0],
    ]);

    expect($analysis['within_tolerance'])->toBe(1)
        ->and($analysis['beyond_tolerance'])->toBe([]);
});

it('resta oltre tolleranza quando la differenza assoluta supera anche la soglia in minuti', function (): void {
    // v1 173.28h, v2 2h: scostamento sia percentuale (~99%) sia assoluto
    // (~10277') enorme — un caso reale (ticket v1 #2855 prima del fix
    // dell'algoritmo, US-219), non rumore di arrotondamento.
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 2855, 'v1_hours' => 173.28, 'v2_minutes' => 120],
    ]);

    expect($analysis['within_tolerance'])->toBe(0)
        ->and($analysis['beyond_tolerance'])->toHaveCount(1)
        ->and($analysis['beyond_tolerance'][0]['id'])->toBe(2855);
});

it('usa una soglia assoluta in minuti personalizzata quando indicata', function (): void {
    // 30 minuti di scostamento assoluto (v1 0.6h=36', v2 6') restano oltre
    // tolleranza con la soglia di default (15'), ma entro con una soglia di 60'.
    $rows = [['id' => 1, 'v1_hours' => 0.6, 'v2_minutes' => 6]];

    $default = WorkedHoursDeviationAnalyzer::analyze($rows);
    $wider = WorkedHoursDeviationAnalyzer::analyze($rows, toleranceAbsoluteMinutes: 60);

    expect($default['within_tolerance'])->toBe(0)
        ->and($wider['within_tolerance'])->toBe(1);
});

it('riporta lo scostamento assoluto in minuti per ogni ticket oltre tolleranza', function (): void {
    $analysis = WorkedHoursDeviationAnalyzer::analyze([
        ['id' => 42, 'v1_hours' => 10.0, 'v2_minutes' => 12 * 60],
    ]);

    expect($analysis['beyond_tolerance'][0]['deviation_minutes'])->toBe(120.0);
});
