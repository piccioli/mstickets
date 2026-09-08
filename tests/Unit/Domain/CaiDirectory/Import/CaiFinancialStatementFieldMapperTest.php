<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiFinancialStatementFieldMapper;

test('mapFinancialStatement maps the 15 Italian-named analyzer fields to CaiFinancialStatement columns', function (): void {
    $row = (object) [
        'oneri_a_interesse_generale' => 1000.0,
        'oneri_b_attivita_diverse' => null,
        'oneri_c_raccolta_fondi' => null,
        'oneri_d_finanziarie_patrimoniali' => null,
        'oneri_e_supporto_generale' => 200.0,
        'totale_oneri' => 1200.0,
        'proventi_a_interesse_generale' => 1500.0,
        'proventi_b_attivita_diverse' => null,
        'proventi_c_raccolta_fondi' => null,
        'proventi_d_finanziarie_patrimoniali' => null,
        'proventi_e_supporto_generale' => null,
        'totale_proventi' => 1500.0,
        'risultato_ante_imposte' => 300.0,
        'imposte' => 50.0,
        'risultato_esercizio' => 250.0,
    ];

    expect(CaiFinancialStatementFieldMapper::mapFinancialStatement($row))->toBe([
        'general_interest_expenses' => 1000.0,
        'other_activities_expenses' => null,
        'fundraising_expenses' => null,
        'financial_expenses' => null,
        'overhead_expenses' => 200.0,
        'total_expenses' => 1200.0,
        'general_interest_revenues' => 1500.0,
        'other_activities_revenues' => null,
        'fundraising_revenues' => null,
        'financial_revenues' => null,
        'overhead_revenues' => null,
        'total_revenues' => 1500.0,
        'pre_tax_result' => 300.0,
        'taxes' => 50.0,
        'net_result' => 250.0,
    ]);
});
