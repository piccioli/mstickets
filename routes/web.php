<?php

declare(strict_types=1);

use App\Http\Controllers\TicketAttachmentDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Allegati dei messaggi del ticket (§9.6 del PRD, US-107): mai un URL medialibrary
// diretto, l'autorizzazione vive nel controller (delega a TicketPolicy::view()).
Route::get('/ticket-attachments/{media}', TicketAttachmentDownloadController::class)
    ->middleware('auth')
    ->name('ticket-attachments.download');
