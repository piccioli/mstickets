<?php

declare(strict_types=1);

namespace App\Domain\Mail\Enums;

/**
 * Ruoli delle cartelle IMAP usate dalla pipeline inbound (§7.3.3, §7.4 del
 * PRD, US-301): il nome reale di ogni cartella sul server è configurato in
 * `config('mail_pipeline.folders')`, mai una stringa letterale nel codice.
 */
enum ImapFolderRole: string
{
    case Inbox = 'inbox';
    case Processed = 'processed';
    case Errors = 'errors';
    case Quarantine = 'quarantine';
}
