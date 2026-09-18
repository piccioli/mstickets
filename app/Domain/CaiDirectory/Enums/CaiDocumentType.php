<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;
use App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument;
use Filament\Support\Contracts\HasLabel;

/**
 * Vocabolario dei tipi di documento per un `CaiDocument` caricato MANUALMENTE (nomenclatura bilanci
 * sezionali CAI fornita dal committente) — mai per un documento sincronizzato da RUNTS: quel percorso
 * ({@see SyncCaiRuntsRegistration}) scrive `document_type` con il "tipo"
 * testuale libero già presente nella risposta RUNTS (es. `bilancio_esercizio`, `statuto`), un vocabolario
 * indipendente che non passa da questo enum. `document_type` resta una colonna stringa semplice (mai
 * castata su questo enum): i valori RUNTS pre-esistenti non ci rientrerebbero.
 */
enum CaiDocumentType: string implements HasLabel
{
    case ModA = 'mod_a';
    case ModB = 'mod_b';
    case ModD = 'mod_d';
    case RelazioneMissione = 'relazione_missione';
    case RelazioneRevisori = 'relazione_revisori';
    case RelazioneAttivita = 'relazione_attivita';
    case VerbaleAssemblea = 'verbale_assemblea';
    case BilancioSociale = 'bilancio_sociale';
    case RelazioneBilancio = 'relazione_bilancio';
    case BilancioAnalitico = 'bilancio_analitico';
    case BilancioRiclassificato = 'bilancio_riclassificato';
    case BilancioEconomicoFinanziario = 'bilancio_economico_finanziario';
    case ModAB = 'mod_a_b';
    case CompletoABC = 'completo_a_b_c';
    case CompletoDRevisori = 'completo_d_revisori';
    case Altro = 'altro';

    public function getLabel(): string
    {
        return match ($this) {
            self::ModA => 'Mod A - Stato Patrimoniale',
            self::ModB => 'Mod B - Rendiconto gestionale',
            self::ModD => 'Mod D - Rendiconto per cassa',
            self::RelazioneMissione => 'Relazione di missione',
            self::RelazioneRevisori => 'Relazione revisori',
            self::RelazioneAttivita => 'Relazione attività',
            self::VerbaleAssemblea => 'Verbale assemblea',
            self::BilancioSociale => 'Bilancio sociale',
            self::RelazioneBilancio => 'Relazione al bilancio',
            self::BilancioAnalitico => 'Bilancio analitico contabile',
            self::BilancioRiclassificato => 'Bilancio riclassificato',
            self::BilancioEconomicoFinanziario => 'Bilancio economico e finanziario',
            self::ModAB => 'Mod A e B - Stato Patrimoniale e Rendiconto gestionale',
            self::CompletoABC => 'Bilancio completo - Mod A, Mod B e Relazione di missione',
            self::CompletoDRevisori => 'Bilancio completo - Mod D e Relazione revisori',
            self::Altro => 'Altro',
        };
    }

    /**
     * Solo gli schemi numerici Mod A/B/D (e le combinazioni che li contengono) alimentano l'estrazione
     * automatica delle cifre di bilancio ({@see AnalyzeCaiFinancialStatementDocument}):
     * relazioni, verbali e bilancio sociale sono narrativi, mai strutturati nello stesso modo.
     */
    public function triggersFinancialAnalysis(): bool
    {
        return match ($this) {
            self::ModA, self::ModB, self::ModD, self::ModAB, self::CompletoABC, self::CompletoDRevisori => true,
            default => false,
        };
    }
}
