<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;

/**
 * Risolve `config('mail_pipeline.staff_notification_group')` (indirizzi
 * email, comma-separated, US-301) nei relativi `User` (§7.5.2, US-312): unico
 * punto di lettura del gruppo staff, così E3/E9 cambiano destinatari
 * cambiando la configurazione, mai un elenco di sviluppatori hard-coded nel
 * Mailable/listener (problema 10 del v1). Un indirizzo in configurazione che
 * non corrisponde a nessun utente è ignorato silenziosamente (es. un
 * indirizzo esterno al pannello), mai un'eccezione.
 */
final class StaffNotificationGroup
{
    /**
     * @return Collection<int, User>
     */
    public static function recipients(): Collection
    {
        $emails = array_values(array_filter(array_map(
            static fn (string $email): string => mb_strtolower(trim($email)),
            config('mail_pipeline.staff_notification_group'),
        )));

        if ($emails === []) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($emails), '?'));

        return User::query()
            ->active()
            ->whereRaw("lower(email) in ({$placeholders})", $emails)
            ->get();
    }
}
