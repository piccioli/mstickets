<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiHutContractStatus;
use App\Domain\CaiDirectory\Models\CaiHut;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Scadenza del contratto di gestione di un rifugio: il solo punto in cui si calcolano stato e testo, condiviso dalla
 * scheda del rifugio ({@see CaiHutSheet}, 10.4) e dai rifugi del GR (10.5). Regole dello script dei design
 * `rifugi-di-sezione.dc.html`/`rifugi-del-gr.dc.html`: scaduto se la fine è prima di oggi, in scadenza entro
 * {@see CaiHutContractStatus::EXPIRING_DAYS} giorni, altrimenti valido; senza una data di fine nessuno stato.
 */
final readonly class CaiHutContractExpiry
{
    public function __construct(public ?CarbonImmutable $endDate) {}

    /** Legge `management.contract.end_date` (`Y-m-d`); un valore assente o malformato non ha scadenza. */
    public static function of(CaiHut $hut): self
    {
        $management = $hut->management ?? [];
        $contract = is_array($management['contract'] ?? null) ? $management['contract'] : [];

        return new self(self::date($contract['end_date'] ?? null));
    }

    public static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value)) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', trim($value)) ?: null;
    }

    /** Giorni da oggi alla fine del contratto (negativi se scaduto), `null` senza data. */
    public function daysLeft(CarbonInterface $today): ?int
    {
        return $this->endDate === null
            ? null
            : (int) CarbonImmutable::parse($today->format('Y-m-d'))->diffInDays($this->endDate, false);
    }

    /** `null` se il contratto non ha una data di scadenza. */
    public function status(CarbonInterface $today): ?CaiHutContractStatus
    {
        $days = $this->daysLeft($today);

        return $days === null ? null : CaiHutContractStatus::fromDaysLeft($days);
    }

    /** "Scaduto da 4 mesi" / "Scade tra 12 giorni" / "Scade oggi", solo per un contratto scaduto o in scadenza. */
    public function note(CarbonInterface $today): ?string
    {
        $days = $this->daysLeft($today);
        $status = $this->status($today);

        if ($days === null || $status === null || ! $status->isAlert()) {
            return null;
        }

        if ($days === 0) {
            return 'Scade oggi';
        }

        $months = (int) round(abs($days) / 30.44);
        $span = $months >= 1
            ? $months.' '.($months === 1 ? 'mese' : 'mesi')
            : abs($days).' '.(abs($days) === 1 ? 'giorno' : 'giorni');

        return $status === CaiHutContractStatus::Expired ? 'Scaduto da '.$span : 'Scade tra '.$span;
    }
}
