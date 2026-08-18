<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\ImapFolderRole;

test('contains exactly inbox, processed, errors and quarantine', function (): void {
    expect(array_map(fn (ImapFolderRole $role): string => $role->value, ImapFolderRole::cases()))
        ->toBe(['inbox', 'processed', 'errors', 'quarantine']);
});
