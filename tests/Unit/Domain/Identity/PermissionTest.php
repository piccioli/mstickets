<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the permission catalog of PRD §9.3', function (): void {
    $expected = [
        // Ticket
        'ticket.view.any', 'ticket.view.own', 'ticket.view.assigned', 'ticket.create',
        'ticket.update.any', 'ticket.update.own', 'ticket.update.assigned', 'ticket.delete',
        'ticket.assign', 'ticket.transition.any', 'ticket.manage-internal-fields',
        // Messaggi ticket
        'ticket-message.create', 'ticket-message.view.internal', 'ticket-message.create.internal',
        // Log ticket
        'ticket-log.view',
        // Tag / commesse
        'tag.view', 'tag.create', 'tag.update', 'tag.delete',
        // Documentazione
        'documentation.view.customer', 'documentation.view.internal', 'documentation.create',
        'documentation.update', 'documentation.delete',
        // Report attività
        'activity-report.view.any', 'activity-report.view.own', 'activity-report.create',
        'activity-report.update', 'activity-report.delete', 'activity-report.generate-pdf',
        // Organizzazioni
        'organization.view', 'organization.create', 'organization.update', 'organization.delete',
        // Fundraising
        'fundraising.view.any', 'fundraising.view.involved', 'fundraising.create',
        'fundraising.update', 'fundraising.delete', 'fundraising.evaluate',
        // Utenti
        'user.view', 'user.create', 'user.update', 'user.deactivate', 'user.assign-roles',
        'user.grant-permissions', 'user.impersonate',
        // Email
        'email.view', 'email.manage',
        // Sistema
        'horizon.access', 'logs.access', 'import.view',
    ];

    $actual = array_map(fn (Permission $permission): string => $permission->value, Permission::cases());

    expect($actual)->toHaveCount(52)
        ->and($actual)->toEqualCanonicalizing($expected);
});

test('implements the Filament label contract', function (): void {
    expect(Permission::TicketViewAny)->toBeInstanceOf(HasLabel::class);
});

test('every case has a non-empty localized label', function (): void {
    foreach (Permission::cases() as $permission) {
        expect($permission->getLabel())->not->toBeEmpty();
    }
});
