<?php

declare(strict_types=1);

use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Enums\ImapFolderRole;
use App\Domain\Mail\Transports\WebklexImapTransport;

test('the container resolves InboundMailTransport to the webklex implementation', function (): void {
    expect(app(InboundMailTransport::class))->toBeInstanceOf(WebklexImapTransport::class);
});

test('fetch fails fast on a misconfigured inbox folder role, before ever attempting to connect', function (): void {
    $transport = new WebklexImapTransport(accountConfig: [], folders: []);

    expect(fn () => $transport->fetch(50))
        ->toThrow(RuntimeException::class, 'Nessuna cartella IMAP configurata per il ruolo [inbox].');
});

test('move fails fast on a misconfigured target folder role, before ever attempting to connect', function (): void {
    $transport = new WebklexImapTransport(accountConfig: [], folders: []);

    expect(fn () => $transport->move('INBOX', 1, ImapFolderRole::Quarantine))
        ->toThrow(RuntimeException::class, 'Nessuna cartella IMAP configurata per il ruolo [quarantine].');
});

test('disconnect is a no-op when no connection was ever opened (US-302)', function (): void {
    $transport = new WebklexImapTransport(accountConfig: [], folders: []);

    $transport->disconnect();
})->throwsNoExceptions();
