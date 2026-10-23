<?php

declare(strict_types=1);
use Illuminate\Testing\TestView;

function metricBadgeSimple(int|float|null $value, ?string $unit = null, int $decimals = 0): TestView
{
    return test()->blade(
        '<x-cai.metric-badge variant="semplice" href="/admin/soci" label="Soci" :value="$value" :unit="$unit" :decimals="$decimals" />',
        ['value' => $value, 'unit' => $unit, 'decimals' => $decimals],
    );
}

/**
 * @param  list<array{label: string, value: float|null}>  $secondary
 */
function metricBadgeBalance(?float $mainValue, array $secondary, ?int $year = 2025, bool $result = true): TestView
{
    return test()->blade(
        '<x-cai.metric-badge variant="bilancio" href="/admin/bilancio" :title="$title" :main-label="$mainLabel" :main-value="$mainValue" :year="$year" :result="$result" :secondary="$secondary" />',
        [
            'title' => $result ? 'Conto economico' : 'Stato patrimoniale',
            'mainLabel' => $result ? 'Risultato netto' : 'Patrimonio netto',
            'mainValue' => $mainValue,
            'year' => $year,
            'result' => $result,
            'secondary' => $secondary,
        ],
    );
}

test('simple badge with a value is a link showing the formatted number', function (): void {
    metricBadgeSimple(1248)
        ->assertSee('<a', false)
        ->assertSee('href="/admin/soci"', false)
        ->assertSee('Soci')
        ->assertSee('1.248')
        ->assertDontSee('Non disponibile')
        ->assertDontSee('ms-metric-badge--unavailable', false);
});

test('simple badge shows kilometers with one decimal and the unit', function (): void {
    metricBadgeSimple(412.63, 'km', 1)
        ->assertSeeInOrder(['412,6', 'km']);
});

test('simple badge shows zero as 0, not as unavailable', function (): void {
    metricBadgeSimple(0)
        ->assertSee('>0<', false)
        ->assertDontSee('Non disponibile');
});

test('simple badge without a value shows the dash, "Non disponibile" and keeps the link', function (): void {
    metricBadgeSimple(null, 'km', 1)
        ->assertSee('—')
        ->assertSee('Non disponibile')
        ->assertSee('ms-metric-badge--unavailable', false)
        ->assertSee('href="/admin/soci"', false)
        ->assertDontSee('>km<', false);
});

test('balance badge with all values shows result, surplus label, year and secondary values', function (): void {
    metricBadgeBalance(14110.0, [
        ['label' => 'Ricavi', 'value' => 186420.0],
        ['label' => 'Costi', 'value' => 172310.0],
    ])
        ->assertSee('href="/admin/bilancio"', false)
        ->assertSeeInOrder(['Conto economico', 'Risultato netto', 'Avanzo', '+14.110 €', 'Esercizio 2025', 'Ricavi', '186.420 €', 'Costi', '172.310 €'])
        ->assertDontSee('Disavanzo')
        ->assertDontSee('ms-metric-badge__main-value--negative', false)
        ->assertDontSee('Non disponibile');
});

test('balance badge with a negative result shows it in red with the deficit label', function (): void {
    metricBadgeBalance(-12340.0, [
        ['label' => 'Ricavi', 'value' => 100000.0],
        ['label' => 'Costi', 'value' => 112340.0],
    ])
        ->assertSee('Disavanzo')
        ->assertSee("\u{2212}".'12.340 €')
        ->assertSee('ms-metric-badge__main-value--negative', false)
        ->assertSee('ms-metric-badge__pill--deficit', false);
});

test('balance badge with a zero result shows 0 € as a surplus', function (): void {
    metricBadgeBalance(0.0, [['label' => 'Ricavi', 'value' => 0.0], ['label' => 'Costi', 'value' => 0.0]])
        ->assertSee('Avanzo')
        ->assertSee('0 €')
        ->assertDontSee('+0 €')
        ->assertDontSee('Non disponibile');
});

test('balance badge marks only the single missing value as unavailable', function (): void {
    $view = metricBadgeBalance(14110.0, [
        ['label' => 'Ricavi', 'value' => 186420.0],
        ['label' => 'Costi', 'value' => null],
    ]);

    $view->assertSeeInOrder(['+14.110 €', 'Esercizio 2025', 'Ricavi', '186.420 €', 'Costi', '—', 'Non disponibile'])
        ->assertDontSee('ms-metric-badge--unavailable', false);
    expect(substr_count((string) $view, 'Non disponibile'))->toBe(1);
});

test('balance badge with a missing main value keeps the secondary values and no pill', function (): void {
    metricBadgeBalance(null, [
        ['label' => 'Ricavi', 'value' => 186420.0],
        ['label' => 'Costi', 'value' => 172310.0],
    ])
        ->assertSeeInOrder(['Risultato netto', '—', 'Non disponibile', 'Esercizio 2025', '186.420 €'])
        ->assertDontSee('Avanzo')
        ->assertDontSee('Disavanzo');
});

test('balance badge without any data is grey with title, main label, dash and "Non disponibile" only', function (): void {
    $view = metricBadgeBalance(null, [
        ['label' => 'Ricavi', 'value' => null],
        ['label' => 'Costi', 'value' => null],
    ], year: null);

    $view->assertSee('ms-metric-badge--unavailable', false)
        ->assertSeeInOrder(['Conto economico', 'Risultato netto', '—', 'Non disponibile'])
        ->assertSee('href="/admin/bilancio"', false)
        ->assertDontSee('Esercizio')
        ->assertDontSee('Ricavi')
        ->assertDontSee('Avanzo');
    expect(substr_count((string) $view, 'Non disponibile'))->toBe(1);
});

test('balance sheet shows a negative net equity with the minus sign, without label and without red', function (): void {
    metricBadgeBalance(-5200.0, [['label' => 'Totale attivo', 'value' => 48000.0]], result: false)
        ->assertSeeInOrder(['Stato patrimoniale', 'Patrimonio netto', "\u{2212}".'5.200 €', 'Esercizio 2025', 'Totale attivo', '48.000 €'])
        ->assertDontSee('Avanzo')
        ->assertDontSee('Disavanzo')
        ->assertDontSee('ms-metric-badge__main-value--negative', false);
});

test('balance sheet shows a positive net equity without the plus sign', function (): void {
    metricBadgeBalance(5200.0, [['label' => 'Totale attivo', 'value' => 48000.0]], result: false)
        ->assertSee('5.200 €')
        ->assertDontSee('+5.200 €');
});

test('simple badge shows the note under the number when the value is present', function (): void {
    test()->blade(
        '<x-cai.metric-badge variant="semplice" href="/admin/gr/soci" label="Soci" :value="$value" note="Somma di 42 Sezioni" />',
        ['value' => 12480],
    )
        ->assertSeeInOrder(['Soci', '12.480', 'Somma di 42 Sezioni', 'Vedi dettaglio'])
        ->assertSee('ms-metric-badge__note', false);
});

test('simple badge never shows the note when the value is unavailable', function (): void {
    test()->blade(
        '<x-cai.metric-badge variant="semplice" href="/admin/gr/rifugi" label="Rifugi" :value="null" note="Somma di 42 Sezioni" />',
    )
        ->assertSee('Non disponibile')
        ->assertDontSee('Somma di 42 Sezioni')
        ->assertDontSee('ms-metric-badge__note', false);
});

test('simple badge without the note prop renders no note element', function (): void {
    metricBadgeSimple(1248)->assertDontSee('ms-metric-badge__note', false);
});

test('balance badge appends the year note to the fiscal year line', function (): void {
    test()->blade(
        '<x-cai.metric-badge variant="bilancio" href="/admin/gr/bilancio" title="Conto economico consolidato Sezioni" main-label="Risultato netto" :main-value="90143.67" :year="2025" :result="true" year-note="Somma di 11 Sezioni" :secondary="$secondary" />',
        ['secondary' => [['label' => 'Ricavi', 'value' => 1520538.66], ['label' => 'Costi', 'value' => 1430394.99]]],
    )
        ->assertSee('Esercizio 2025 · Somma di 11 Sezioni')
        ->assertSeeInOrder(['+90.144 €', 'Esercizio 2025 · Somma di 11 Sezioni', 'Ricavi']);
});

test('balance badge hides the year note when there is no fiscal year', function (): void {
    test()->blade(
        '<x-cai.metric-badge variant="bilancio" href="/admin/gr/bilancio" title="Stato patrimoniale consolidato Sezioni" main-label="Patrimonio netto" :main-value="null" :year="null" year-note="Somma di 8 Sezioni" :secondary="$secondary" />',
        ['secondary' => [['label' => 'Totale attivo', 'value' => null]]],
    )
        ->assertSee('Non disponibile')
        ->assertDontSee('Esercizio')
        ->assertDontSee('Somma di 8 Sezioni');
});

test('balance badge without the year note keeps the plain fiscal year line', function (): void {
    $view = metricBadgeBalance(14110.0, [['label' => 'Ricavi', 'value' => 186420.0], ['label' => 'Costi', 'value' => 172310.0]]);

    $view->assertSee('Esercizio 2025</span>', false)
        ->assertDontSee(' · ');
});

test('a pending simple badge shows "In attesa" with the same muted style', function (): void {
    test()->blade('<x-cai.metric-badge href="/admin/tappe" label="Tappe SICAI" :value="null" pending />')
        ->assertSee('ms-metric-badge--unavailable', false)
        ->assertSee('ms-metric-badge--pending', false)
        ->assertSee('In attesa')
        ->assertDontSee('Non disponibile');
});

test('a pending badge with a value shows the value', function (): void {
    test()->blade('<x-cai.metric-badge href="/admin/tappe" label="Tappe SICAI" :value="12" pending />')
        ->assertSee('12')
        ->assertDontSee('In attesa')
        ->assertDontSee('ms-metric-badge--pending', false)
        ->assertDontSee('ms-metric-badge--unavailable', false);
});
