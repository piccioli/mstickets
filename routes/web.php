<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityReportPdfDownloadController;
use App\Http\Controllers\CaiDocumentDownloadController;
use App\Http\Controllers\DocumentationPagePdfDownloadController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TicketAttachmentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

// Allegati dei messaggi del ticket (§9.6 del PRD, US-107): mai un URL medialibrary
// diretto, l'autorizzazione vive nel controller (delega a TicketPolicy::view()).
Route::get('/ticket-attachments/{media}', TicketAttachmentDownloadController::class)
    ->middleware('auth')
    ->name('ticket-attachments.download');

// PDF generato di una pagina di documentazione (§6.4.3 del PRD, US-406): mai un
// URL diretto sul disco, l'autorizzazione vive nel controller (delega a
// DocumentationPagePolicy::view()).
Route::get('/documentation-pages/{documentationPage}/pdf', DocumentationPagePdfDownloadController::class)
    ->middleware('auth')
    ->name('documentation-pages.pdf-download');

// PDF generato di un report di attività (§6.5.3 del PRD, US-409): mai un URL
// diretto sul disco, l'autorizzazione vive nel controller (delega a
// ActivityReportPolicy::view()).
Route::get('/activity-reports/{activityReport}/pdf', ActivityReportPdfDownloadController::class)
    ->middleware('auth')
    ->name('activity-reports.pdf-download');

// Documento (`cai_documents`) importato dal datapack RUNTS-CAI (§9 del design doc,
// US-804): mai un URL diretto sul disco privato `cai-documents`, l'autorizzazione vive
// nel controller (`Permission::CaiDirectoryView`).
Route::get('/cai-documents/{caiDocument}/download', CaiDocumentDownloadController::class)
    ->middleware('auth')
    ->name('cai-documents.download');
