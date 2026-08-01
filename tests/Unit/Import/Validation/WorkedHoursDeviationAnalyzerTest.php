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
