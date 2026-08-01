<?php

declare(strict_types=1);

use App\Import\Stages\ActivityReportsStage;
use App\Import\Stages\ActivityReportTicketsStage;
use App\Import\Stages\DocumentationStage;
use App\Import\Stages\FundraisingOpportunitiesStage;
use App\Import\Stages\FundraisingPartnersStage;
use App\Import\Stages\FundraisingProjectsStage;
use App\Import\Stages\FundraisingScoresStage;
use App\Import\Stages\OrganizationMembersStage;
use App\Import\Stages\OrganizationsStage;
use App\Import\Stages\RolesPermissionsStage;
use App\Import\Stages\TagsStage;
use App\Import\Stages\TicketAttachmentsStage;
use App\Import\Stages\TicketHierarchyStage;
use App\Import\Stages\TicketLogsStage;
use App\Import\Stages\TicketMessagesStage;
use App\Import\Stages\TicketParticipantsStage;
use App\Import\Stages\TicketsStage;
use App\Import\Stages\TicketTagsStage;
use App\Import\Stages\TicketViewsStage;
use App\Import\Stages\UsersStage;

return [

    /*
    |--------------------------------------------------------------------------
    | Stage dell'ETL v1→v2 (§11.4 del PRD)
    |--------------------------------------------------------------------------
    |
    | Elenco delle classi che implementano App\Import\Stages\Contracts\ImportStage,
    | risolte via il container e registrate in App\Import\Stages\ImportStageRegistry
    | nell'ordine in cui compaiono qui sotto (l'ordine di REGISTRAZIONE non conta:
    | ImportRunner::plan() risolve l'ordine di ESECUZIONE dalle dipendenze
    | dichiarate da ciascuno stage).
    |
    | Le fasi successive aggiungono qui la propria classe, senza toccare il
    | comando v1:import né il runner (US-201).
    |
    */

    'stages' => [
        UsersStage::class,
        RolesPermissionsStage::class,
        OrganizationsStage::class,
        OrganizationMembersStage::class,
        DocumentationStage::class,
        TagsStage::class,
        TicketsStage::class,
        TicketHierarchyStage::class,
        TicketTagsStage::class,
        TicketParticipantsStage::class,
        TicketLogsStage::class,
        TicketViewsStage::class,
        TicketMessagesStage::class,
        TicketAttachmentsStage::class,
        ActivityReportsStage::class,
        ActivityReportTicketsStage::class,
        FundraisingOpportunitiesStage::class,
        FundraisingScoresStage::class,
        FundraisingProjectsStage::class,
        FundraisingPartnersStage::class,
    ],

];
