<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Catalogo dei criteri di valutazione (§6.6.2): chiave, gruppo, etichetta e range di
 * punteggio ammesso. Vive in PHP, non in una tabella: aggiungere un criterio è una nuova
 * case qui, mai una migrazione su fundraising_evaluation_scores.
 */
enum FundraisingEvaluationCriterion: string implements HasLabel
{
    case CriterionA = 'criterion_a';
    case CriterionB = 'criterion_b';
    case CriterionC = 'criterion_c';
    case CriterionD = 'criterion_d';
    case CriterionE = 'criterion_e';
    case CriterionF = 'criterion_f';

    case BaseCoerenzaBando = 'base_coerenza_bando';
    case BaseCapofilaIdoneo = 'base_capofila_idoneo';
    case BasePartnerMinimi = 'base_partner_minimi';
    case BaseCofinanziamento = 'base_cofinanziamento';
    case BaseTempistiche = 'base_tempistiche';

    case QualCoerenzaCai = 'qual_coerenza_cai';
    case QualImpAmbientale = 'qual_imp_ambientale';
    case QualImpSociale = 'qual_imp_sociale';
    case QualImpEconomico = 'qual_imp_economico';
    case QualObiettiviChiari = 'qual_obiettivi_chiari';
    case QualSoliditaAzioni = 'qual_solidita_azioni';
    case QualCapacitaPartner = 'qual_capacita_partner';

    case PremInnovazione = 'prem_innovazione';
    case PremReplicabilita = 'prem_replicabilita';
    case PremComunita = 'prem_comunita';
    case PremSostenibilita = 'prem_sostenibilita';

    case RiskTecnici = 'risk_tecnici';
    case RiskFinanziari = 'risk_finanziari';
    case RiskOrganizzativi = 'risk_organizzativi';
    case RiskLogistici = 'risk_logistici';

    public function getLabel(): string
    {
        return match ($this) {
            self::CriterionA => 'Coerenza e rilevanza',
            self::CriterionB => 'Qualità dell\'idea e fattibilità',
            self::CriterionC => 'Impatto su soci, territorio, comunità',
            self::CriterionD => 'Valore aggiunto e replicabilità',
            self::CriterionE => 'Partenariato e capacità operativa',
            self::CriterionF => 'Sostenibilità economica',
            self::BaseCoerenzaBando => 'Coerenza con il bando',
            self::BaseCapofilaIdoneo => 'Capofila idoneo',
            self::BasePartnerMinimi => 'Partner minimi richiesti',
            self::BaseCofinanziamento => 'Cofinanziamento disponibile',
            self::BaseTempistiche => 'Tempistiche compatibili',
            self::QualCoerenzaCai => 'Coerenza con la missione CAI',
            self::QualImpAmbientale => 'Impatto ambientale',
            self::QualImpSociale => 'Impatto sociale',
            self::QualImpEconomico => 'Impatto economico',
            self::QualObiettiviChiari => 'Obiettivi chiari',
            self::QualSoliditaAzioni => 'Solidità delle azioni',
            self::QualCapacitaPartner => 'Capacità dei partner',
            self::PremInnovazione => 'Innovazione',
            self::PremReplicabilita => 'Replicabilità',
            self::PremComunita => 'Coinvolgimento della comunità',
            self::PremSostenibilita => 'Sostenibilità',
            self::RiskTecnici => 'Rischi tecnici',
            self::RiskFinanziari => 'Rischi finanziari',
            self::RiskOrganizzativi => 'Rischi organizzativi',
            self::RiskLogistici => 'Rischi logistici',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::CriterionA, self::CriterionB, self::CriterionC,
            self::CriterionD, self::CriterionE, self::CriterionF => 'criteri_principali',
            self::BaseCoerenzaBando, self::BaseCapofilaIdoneo, self::BasePartnerMinimi,
            self::BaseCofinanziamento, self::BaseTempistiche => 'requisiti_base',
            self::QualCoerenzaCai, self::QualImpAmbientale, self::QualImpSociale,
            self::QualImpEconomico, self::QualObiettiviChiari, self::QualSoliditaAzioni,
            self::QualCapacitaPartner => 'qualitativi',
            self::PremInnovazione, self::PremReplicabilita,
            self::PremComunita, self::PremSostenibilita => 'premiali',
            self::RiskTecnici, self::RiskFinanziari,
            self::RiskOrganizzativi, self::RiskLogistici => 'rischi',
        };
    }

    public function min(): int
    {
        return match ($this) {
            self::RiskFinanziari => -3,
            self::RiskOrganizzativi, self::RiskLogistici => -2,
            default => 0,
        };
    }

    public function max(): int
    {
        return match ($this) {
            self::CriterionA, self::CriterionB, self::CriterionC,
            self::CriterionD, self::CriterionE, self::CriterionF,
            self::QualCoerenzaCai, self::QualImpAmbientale, self::QualImpSociale,
            self::QualImpEconomico, self::QualObiettiviChiari, self::QualSoliditaAzioni,
            self::QualCapacitaPartner => 5,
            self::BaseCoerenzaBando, self::BaseCapofilaIdoneo, self::BasePartnerMinimi,
            self::BaseCofinanziamento, self::BaseTempistiche => 1,
            self::PremInnovazione, self::PremReplicabilita, self::PremComunita,
            self::PremSostenibilita, self::RiskTecnici => 3,
            self::RiskFinanziari => 3,
            self::RiskOrganizzativi, self::RiskLogistici => 2,
        };
    }
}
