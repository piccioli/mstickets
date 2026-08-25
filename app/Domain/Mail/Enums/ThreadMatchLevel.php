<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Livello con cui `App\Domain\Mail\Actions\ResolveEmailThread` ha risolto il
 * thread di un'email inbound (§7.3.6, US-306), in ordine di affidabilità
 * decrescente. `Heuristic` è l'unico livello non deterministico: va sempre
 * mostrato esplicitamente in amministrazione (US-321), mai confuso con un
 * match certo.
 */
enum ThreadMatchLevel: string implements HasLabel
{
    case Verp = 'verp';
    case InReplyTo = 'in_reply_to';
    case SubjectToken = 'subject_token';
    case Heuristic = 'heuristic';

    public function getLabel(): string
    {
        return match ($this) {
            self::Verp => 'Indirizzo VERP (ticket+ulid)',
            self::InReplyTo => 'In-Reply-To/References',
            self::SubjectToken => 'Token subject [#id]',
            self::Heuristic => 'Euristica (mittente + subject)',
        };
    }
}
