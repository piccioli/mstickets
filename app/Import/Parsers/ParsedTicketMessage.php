<?php

declare(strict_types=1);

namespace App\Import\Parsers;

use Illuminate\Support\Carbon;

/**
 * Un singolo messaggio ricostruito da {@see CustomerRequestParser}: `postedAt` è
 * `null` quando non ricavabile dal solo testo (il blocco "originale" non ha mai un
 * timestamp proprio nel v1, i blocchi di risposta lo hanno sempre tranne in caso di
 * data malformata) — la risoluzione finale (fallback a `stories.created_at` o
 * distribuzione monotona) è responsabilità dello stage, non del parser, che resta
 * puro/testabile senza il ticket v2 di destinazione.
 */
final readonly class ParsedTicketMessage
{
    public function __construct(
        public ?string $author,
        public ?Carbon $postedAt,
        public string $body,
        public bool $isOriginal,
    ) {}

    public function withPostedAt(Carbon $postedAt): self
    {
        return new self($this->author, $postedAt, $this->body, $this->isOriginal);
    }
}
