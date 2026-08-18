<?php

declare(strict_types=1);

namespace App\Domain\Mail\Transports;

use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Enums\ImapFolderRole;
use App\Domain\Mail\Support\RawInboundEmail;
use DateTimeInterface;
use RuntimeException;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Implementazione di InboundMailTransport con `webklex/php-imap` (libreria
 * standalone, non il wrapper Laravel `webklex/laravel-imap`), configurata
 * interamente da `config('mail_pipeline.imap')`/`config('mail_pipeline.folders')`
 * — mai una chiamata env() qui (§13.3 del PRD, US-301).
 */
final class WebklexImapTransport implements InboundMailTransport
{
    private ?Client $client = null;

    /**
     * @param  array<string, mixed>  $accountConfig  forma attesa da
     *                                               `Webklex\PHPIMAP\ClientManager::make()` (host/port/encryption/
     *                                               validate_cert/username/password), letta da `config('mail_pipeline.imap')`.
     * @param  array<string, string>  $folders  nome cartella reale per ogni
     *                                          `ImapFolderRole::value`, letta da `config('mail_pipeline.folders')`.
     */
    public function __construct(
        private readonly array $accountConfig,
        private readonly array $folders,
    ) {}

    public function fetch(int $limit, ?DateTimeInterface $since = null): array
    {
        $inboxFolderName = $this->folderName(ImapFolderRole::Inbox);

        $folder = $this->client()->getFolder($inboxFolderName);

        $query = $since !== null
            ? $folder->query()->whereSince($since)
            : $folder->query()->whereAll();

        $messages = $query->limit($limit)->get();

        return $messages
            ->map(fn (Message $message): RawInboundEmail => new RawInboundEmail(
                rawMessage: $message->getHeader()?->raw."\r\n\r\n".$message->getRawBody(),
                imapFolder: $folder->path,
                imapUid: (int) $message->uid,
                messageId: $this->attributeToNullableString($message->getMessageId()),
                fromEmail: $message->getFrom()->first()?->mail ?: null,
                fromName: $message->getFrom()->first()?->personal ?: null,
                subject: $this->attributeToNullableString($message->getSubject()),
                to: $this->addressesToEmails($message->getTo()),
                inReplyTo: $this->attributeToNullableString($message->getInReplyTo()),
                references: array_values(array_filter($message->getReferences()->all())),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function addressesToEmails(Attribute $attribute): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $address): ?string => $address->mail ?? null,
            $attribute->all(),
        )));
    }

    public function move(string $imapFolder, int $imapUid, ImapFolderRole $targetFolder): void
    {
        $targetFolderName = $this->folderName($targetFolder);

        $message = $this->client()->getFolder($imapFolder)->query()->getMessageByUid($imapUid);

        $message->move($targetFolderName, expunge: true);
    }

    public function disconnect(): void
    {
        $this->client?->disconnect();
        $this->client = null;
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $this->client = (new ClientManager)->make($this->accountConfig);
            $this->client->connect();
        }

        return $this->client;
    }

    private function attributeToNullableString(Attribute $attribute): ?string
    {
        if ($attribute->count() === 0) {
            return null;
        }

        return $attribute->toString() ?: null;
    }

    private function folderName(ImapFolderRole $role): string
    {
        return $this->folders[$role->value]
            ?? throw new RuntimeException("Nessuna cartella IMAP configurata per il ruolo [{$role->value}].");
    }
}
