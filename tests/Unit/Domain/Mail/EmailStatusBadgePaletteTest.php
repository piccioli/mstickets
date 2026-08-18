<?php

declare(strict_types=1);

use App\Domain\Mail\Support\EmailStatusBadgePalette;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Support\DesignTokens;

test('a status badge reuses the same color category as TicketStatus::getColor(), not a second palette', function (): void {
    expect(EmailStatusBadgePalette::colors(TicketStatus::New))->toBe([
        'background' => DesignTokens::get('ms-status-nuovo-bg'),
        'border' => DesignTokens::get('ms-status-nuovo-border'),
        'text' => DesignTokens::get('ms-text-badge'),
    ]);

    expect(EmailStatusBadgePalette::colors(TicketStatus::Backlog))->toBe([
        'background' => DesignTokens::get('ms-status-backlog-bg'),
        'border' => DesignTokens::get('ms-status-backlog-border'),
        'text' => DesignTokens::get('ms-text-badge'),
    ]);

    expect(EmailStatusBadgePalette::colors(TicketStatus::Done))->toBe([
        'background' => DesignTokens::get('ms-status-completato-bg'),
        'border' => DesignTokens::get('ms-status-completato-border'),
        'text' => DesignTokens::get('ms-text-badge'),
    ]);

    expect(EmailStatusBadgePalette::colors(TicketStatus::Rejected))->toBe([
        'background' => DesignTokens::get('ms-status-non-finanziato-bg'),
        'border' => DesignTokens::get('ms-status-non-finanziato-border'),
        'text' => DesignTokens::get('ms-text-badge'),
    ]);
});

test('every status shares the same badge colors as its getColor() category', function (): void {
    $byCategory = [];

    foreach (TicketStatus::cases() as $status) {
        $byCategory[$status->getColor()][] = EmailStatusBadgePalette::colors($status);
    }

    foreach ($byCategory as $colorsForCategory) {
        expect(array_unique(array_map(serialize(...), $colorsForCategory)))->toHaveCount(1);
    }
});
