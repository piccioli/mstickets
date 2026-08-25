<?php

declare(strict_types=1);

namespace Tests\Support\Mail;

use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Enums\ImapFolderRole;
use App\Domain\Mail\Support\RawInboundEmail;
use DateTimeInterface;

/**
 * Doppio di test di InboundMailTransport (US-302): restituisce un elenco di
 * `RawInboundEmail` già pronto, senza mai aprire una connessione IMAP reale
 * (mai un vero server IMAP nei test). Registra gli argomenti dell'ultima
 * chiamata e se `disconnect()` è stato invocato, per verificare che
 * `mail:fetch-inbound` lo faccia sempre, anche in un `finally`.
 */
final class FakeInboundMailTransport implements InboundMailTransport
{
    public int $fetchCalls = 0;

    public ?int $lastLimit = null;

    public ?DateTimeInterface $lastSince = null;

    public bool $disconnected = false;

    /**
     * @var list<array{imapFolder: string, imapUid: int, targetFolder: ImapFolderRole}>
     */
    public array $moveCalls = [];

    /**
     * @param  list<RawInboundEmail>  $messages
     */
    public function __construct(
        private readonly array $messages = [],
        private readonly ?\Throwable $fetchException = null,
    ) {}

    public function fetch(int $limit, ?DateTimeInterface $since = null): array
    {
        $this->fetchCalls++;
        $this->lastLimit = $limit;
        $this->lastSince = $since;

        if ($this->fetchException !== null) {
            throw $this->fetchException;
        }

        return array_slice($this->messages, 0, $limit);
    }

    public function move(string $imapFolder, int $imapUid, ImapFolderRole $targetFolder): void
    {
        $this->moveCalls[] = ['imapFolder' => $imapFolder, 'imapUid' => $imapUid, 'targetFolder' => $targetFolder];
    }

    public function disconnect(): void
    {
        $this->disconnected = true;
    }
}
