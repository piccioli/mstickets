<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

/**
 * Risultato di `App\Domain\Mail\Parsers\SubjectNormalizer::normalize()` (§7.3.5,
 * US-303): `subject` ha i prefissi Re:/Fwd:/ecc. rimossi (anche in cascata) ma
 * CONSERVA l'eventuale token `[#<id>]` — US-306 (risoluzione thread, livello 3)
 * cerca quel token direttamente nel subject normalizzato già salvato su
 * `email_messages.subject`, non in un campo separato. `ticketId` è lo stesso
 * valore esposto qui solo per comodità dei chiamanti che non vogliono
 * ri-applicare la stessa regex.
 */
final readonly class NormalizedSubject
{
    public function __construct(
        public string $subject,
        public ?int $ticketId,
    ) {}
}
