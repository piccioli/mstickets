<?php

declare(strict_types=1);

namespace App\Domain\Mail\Events;

use App\Domain\Mail\Actions\ApplyInboundEmail;
use App\Domain\Mail\Actions\ResolveEmailSender;
use App\Domain\Mail\Models\EmailMessage;

/**
 * Emesso da {@see ApplyInboundEmail} (§7.3.8, US-308) quando un'email
 * classificata ha un mittente che {@see ResolveEmailSender}
 * non è riuscita a identificare (`status = quarantined`, mai `discarded`).
 *
 * `$autoReplyAllowed` è già stato deciso qui (nessuna soppressione attiva sul
 * mittente al momento della classificazione, §7.3.4/US-304): un listener che
 * invia l'auto-reply al mittente non deve rivalutare i controlli anti-loop,
 * solo rispettare questo flag.
 *
 * Nessun listener è ancora registrato in questa story (stesso pattern già
 * usato per {@see InboundEmailApplied} in US-307): la notifica E9 allo staff
 * e l'auto-reply condizionato al mittente si aggancieranno qui, come listener
 * `ShouldQueue` registrati in `AppServiceProvider::boot()` (US-312).
 */
final readonly class EmailQuarantined
{
    public function __construct(
        public EmailMessage $emailMessage,
        public bool $autoReplyAllowed,
    ) {}
}
