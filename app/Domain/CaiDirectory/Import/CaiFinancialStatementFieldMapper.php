<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Mappatura pura dei 18 campi finanziari (Fase 9, storia 3): stessi nomi italiani già usati dalla tabella
 * `bilanci` del datapack statico E dalla risposta di `POST /analyze/bilancio` del servizio live
 * (`extract_bilancio_pdf()` nel prototipo Python restituisce esattamente queste chiavi, verificato — design
 * doc §3.4/§4.4: nessuna traduzione lato Python, il mapper condiviso vive solo qui). Non include mai
 * `cai_runts_registration_id`/`year`: quei due campi hanno una fonte diversa a seconda del chiamante
 * (colonne dirette della riga datapack per l'import statico, il `CaiDocument` già noto per il job di
 * analisi live) e restano responsabilità del chiamante.
 */
final class CaiFinancialStatementFieldMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function mapFinancialStatement(object $row): array
    {
        return [
            'general_interest_expenses' => $row->oneri_a_interesse_generale,
            'other_activities_expenses' => $row->oneri_b_attivita_diverse,
            'fundraising_expenses' => $row->oneri_c_raccolta_fondi,
            'financial_expenses' => $row->oneri_d_finanziarie_patrimoniali,
            'overhead_expenses' => $row->oneri_e_supporto_generale,
            'total_expenses' => $row->totale_oneri,
            'general_interest_revenues' => $row->proventi_a_interesse_generale,
            'other_activities_revenues' => $row->proventi_b_attivita_diverse,
            'fundraising_revenues' => $row->proventi_c_raccolta_fondi,
            'financial_revenues' => $row->proventi_d_finanziarie_patrimoniali,
            'overhead_revenues' => $row->proventi_e_supporto_generale,
            'total_revenues' => $row->totale_proventi,
            'pre_tax_result' => $row->risultato_ante_imposte,
            'taxes' => $row->imposte,
            'net_result' => $row->risultato_esercizio,
            // Assenti nelle righe del datapack (solo il servizio live li restituisce): mai un errore.
            'total_assets' => $row->totale_attivo ?? null,
            'total_liabilities' => $row->totale_passivo ?? null,
            'net_equity' => $row->patrimonio_netto ?? null,
        ];
    }
}
