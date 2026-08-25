<?php

declare(strict_types=1);

namespace App\Domain\Mail\Contracts;

use App\Domain\Mail\Enums\ImapFolderRole;
use App\Domain\Mail\Support\RawInboundEmail;
use DateTimeInterface;

/**
 * Punto di accesso alla casella email in ingresso (§7.4 del PRD, US-301).
 * L'implementazione di oggi (`App\Domain\Mail\Transports\WebklexImapTransport`)
 * legge da IMAP via polling; un futuro provider a webhook implementerebbe lo
 * stesso contratto senza toccare la pipeline (`mail:fetch-inbound`, US-302, e
 * tutto ciò che segue).
 */
interface InboundMailTransport
{
    /**
     * Legge fino a `$limit` messaggi grezzi dalla cartella inbox, mai "tutti
     * gli unseen": `$limit` è obbligatorio per costruzione (nessun default
     * nell'interfaccia, il chiamante lo risolve da
     * `config('mail_pipeline.fetch.default_limit')`). Con `$since` valorizzato,
     * legge solo i messaggi arrivati da quella data in poi.
     *
     * @return list<RawInboundEmail>
     */
    public function fetch(int $limit, ?DateTimeInterface $since = null): array;

    /**
     * Sposta un messaggio identificato da `$imapFolder`/`$imapUid` nella
     * cartella corrispondente a `$targetFolder` (tipicamente INBOX →
     * Processed/Errors/Quarantine).
     */
    public function move(string $imapFolder, int $imapUid, ImapFolderRole $targetFolder): void;

    /**
     * Chiude la connessione se aperta (mai un'eccezione se non lo è mai
     * stata): il chiamante lo invoca sempre in un `finally`, anche quando
     * `fetch()`/`move()` hanno lanciato un'eccezione (US-302).
     */
    public function disconnect(): void;
}
