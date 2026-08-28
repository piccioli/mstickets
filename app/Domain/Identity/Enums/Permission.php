<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Catalogo dei permessi (PRD §9.3). Convenzione di naming: `<dominio>.<azione>[.<ambito>]`,
 * dove `any` = su qualunque record, `own` = solo sui propri, `assigned` = solo su quelli in
 * cui l'utente è assegnatario o tester. Nessuna interfaccia HasColor/HasIcon: a differenza
 * dei ruoli, un colore/icona per singolo permesso non veicolerebbe informazione utile (§9.3
 * non definisce una tassonomia visiva dei permessi).
 */
enum Permission: string implements HasLabel
{
    // Ticket
    case TicketViewAny = 'ticket.view.any';
    case TicketViewOwn = 'ticket.view.own';
    case TicketViewAssigned = 'ticket.view.assigned';
    case TicketCreate = 'ticket.create';
    case TicketUpdateAny = 'ticket.update.any';
    case TicketUpdateOwn = 'ticket.update.own';
    case TicketUpdateAssigned = 'ticket.update.assigned';
    case TicketDelete = 'ticket.delete';
    case TicketAssign = 'ticket.assign';
    case TicketTransitionAny = 'ticket.transition.any';
    case TicketManageInternalFields = 'ticket.manage-internal-fields';

    // Messaggi ticket
    case TicketMessageCreate = 'ticket-message.create';
    case TicketMessageViewInternal = 'ticket-message.view.internal';
    case TicketMessageCreateInternal = 'ticket-message.create.internal';

    // Log ticket
    case TicketLogView = 'ticket-log.view';

    // Tag / commesse
    case TagView = 'tag.view';
    case TagCreate = 'tag.create';
    case TagUpdate = 'tag.update';
    case TagDelete = 'tag.delete';

    // Documentazione
    case DocumentationViewCustomer = 'documentation.view.customer';
    case DocumentationViewInternal = 'documentation.view.internal';
    case DocumentationCreate = 'documentation.create';
    case DocumentationUpdate = 'documentation.update';
    case DocumentationDelete = 'documentation.delete';

    // Report attività
    case ActivityReportViewAny = 'activity-report.view.any';
    case ActivityReportViewOwn = 'activity-report.view.own';
    case ActivityReportCreate = 'activity-report.create';
    case ActivityReportUpdate = 'activity-report.update';
    case ActivityReportDelete = 'activity-report.delete';
    case ActivityReportGeneratePdf = 'activity-report.generate-pdf';

    // Organizzazioni
    case OrganizationView = 'organization.view';
    case OrganizationCreate = 'organization.create';
    case OrganizationUpdate = 'organization.update';
    case OrganizationDelete = 'organization.delete';

    // Fundraising
    case FundraisingViewAny = 'fundraising.view.any';
    case FundraisingViewInvolved = 'fundraising.view.involved';
    case FundraisingCreate = 'fundraising.create';
    case FundraisingUpdate = 'fundraising.update';
    case FundraisingDelete = 'fundraising.delete';
    case FundraisingEvaluate = 'fundraising.evaluate';

    // Utenti
    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserUpdate = 'user.update';
    case UserDeactivate = 'user.deactivate';
    case UserAssignRoles = 'user.assign-roles';
    case UserGrantPermissions = 'user.grant-permissions';
    case UserImpersonate = 'user.impersonate';

    // Email
    case EmailView = 'email.view';
    case EmailManage = 'email.manage';

    // Sistema
    case HorizonAccess = 'horizon.access';
    case LogsAccess = 'logs.access';
    case ImportView = 'import.view';

    // Anagrafica CAI
    case CaiDirectoryView = 'cai-directory.view';

    public function getLabel(): string
    {
        return match ($this) {
            self::TicketViewAny => 'Visualizzare tutti i ticket',
            self::TicketViewOwn => 'Visualizzare i propri ticket',
            self::TicketViewAssigned => 'Visualizzare i ticket assegnati',
            self::TicketCreate => 'Creare ticket',
            self::TicketUpdateAny => 'Modificare tutti i ticket',
            self::TicketUpdateOwn => 'Modificare i propri ticket',
            self::TicketUpdateAssigned => 'Modificare i ticket assegnati',
            self::TicketDelete => 'Eliminare ticket',
            self::TicketAssign => 'Assegnare ticket',
            self::TicketTransitionAny => 'Cambiare stato a qualunque ticket',
            self::TicketManageInternalFields => 'Gestire i campi interni del ticket',

            self::TicketMessageCreate => 'Scrivere messaggi nei ticket',
            self::TicketMessageViewInternal => 'Visualizzare messaggi interni',
            self::TicketMessageCreateInternal => 'Scrivere messaggi interni',

            self::TicketLogView => 'Visualizzare lo storico dei ticket',

            self::TagView => 'Visualizzare i tag/commesse',
            self::TagCreate => 'Creare tag/commesse',
            self::TagUpdate => 'Modificare tag/commesse',
            self::TagDelete => 'Eliminare tag/commesse',

            self::DocumentationViewCustomer => 'Visualizzare documentazione cliente',
            self::DocumentationViewInternal => 'Visualizzare documentazione interna',
            self::DocumentationCreate => 'Creare pagine di documentazione',
            self::DocumentationUpdate => 'Modificare pagine di documentazione',
            self::DocumentationDelete => 'Eliminare pagine di documentazione',

            self::ActivityReportViewAny => 'Visualizzare tutti i report di attività',
            self::ActivityReportViewOwn => 'Visualizzare i propri report di attività',
            self::ActivityReportCreate => 'Creare report di attività',
            self::ActivityReportUpdate => 'Modificare report di attività',
            self::ActivityReportDelete => 'Eliminare report di attività',
            self::ActivityReportGeneratePdf => 'Generare il PDF del report di attività',

            self::OrganizationView => 'Visualizzare le organizzazioni',
            self::OrganizationCreate => 'Creare organizzazioni',
            self::OrganizationUpdate => 'Modificare organizzazioni',
            self::OrganizationDelete => 'Eliminare organizzazioni',

            self::FundraisingViewAny => 'Visualizzare tutte le opportunità di fundraising',
            self::FundraisingViewInvolved => 'Visualizzare le opportunità di fundraising in cui si è coinvolti',
            self::FundraisingCreate => 'Creare opportunità di fundraising',
            self::FundraisingUpdate => 'Modificare opportunità di fundraising',
            self::FundraisingDelete => 'Eliminare opportunità di fundraising',
            self::FundraisingEvaluate => 'Valutare opportunità di fundraising',

            self::UserView => 'Visualizzare gli utenti',
            self::UserCreate => 'Creare utenti',
            self::UserUpdate => 'Modificare utenti',
            self::UserDeactivate => 'Disattivare utenti',
            self::UserAssignRoles => 'Assegnare ruoli agli utenti',
            self::UserGrantPermissions => 'Concedere permessi diretti agli utenti',
            self::UserImpersonate => 'Impersonare utenti',

            self::EmailView => 'Visualizzare le email',
            self::EmailManage => 'Gestire le email',

            self::HorizonAccess => 'Accedere a Horizon',
            self::LogsAccess => 'Accedere ai log',
            self::ImportView => 'Visualizzare lo stato delle importazioni',

            self::CaiDirectoryView => "Visualizzare l'anagrafica CAI",
        };
    }
}
