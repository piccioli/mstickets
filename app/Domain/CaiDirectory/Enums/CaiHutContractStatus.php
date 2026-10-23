<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

/**
 * Stato della scadenza del contratto di gestione di un rifugio (scheda del rifugio, US-1035), con le soglie dello
 * script del design (`docs/project/design/fase-10/rifugi-di-sezione.dc.html`): scaduto, in scadenza entro 183 giorni
 * (6 mesi), altrimenti valido.
 */
enum CaiHutContractStatus: string
{
    case Valid = 'valid';
    case Expiring = 'expiring';
    case Expired = 'expired';

    /** Giorni entro cui un contratto è "in scadenza". */
    public const int EXPIRING_DAYS = 183;

    public static function fromDaysLeft(int $days): self
    {
        return match (true) {
            $days < 0 => self::Expired,
            $days <= self::EXPIRING_DAYS => self::Expiring,
            default => self::Valid,
        };
    }

    /** Pillola della testata "Gestione"; `null` se il contratto è valido. */
    public function pill(): ?string
    {
        return match ($this) {
            self::Expired => 'Contratto scaduto',
            self::Expiring => 'Contratto in scadenza',
            self::Valid => null,
        };
    }

    public function isAlert(): bool
    {
        return $this !== self::Valid;
    }
}
