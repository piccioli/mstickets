<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use App\Domain\Mail\Support\NotificationRecipientResolver;

/**
 * Ruoli astratti usabili come cella della tabella "attore × transizione →
 * destinatari" di {@see NotificationRecipientResolver}
 * (US-318, §7.5.3 del PRD). Un ruolo non è un `User`: è risolto contro il
 * `Ticket` (o, per `Manager`, contro l'intero pannello) solo dal resolver.
 */
enum NotificationRecipientRole
{
    case Requester;
    case Assignee;
    case Tester;

    /** Tutti gli utenti col ruolo `manager` (§9.5), non un singolo campo del ticket. */
    case Manager;
}
