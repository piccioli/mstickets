<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AddTicketAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a user who can view the ticket can download its attachment', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $viewer = userWithPermissions(Permission::TicketViewAny);
    $message = ticketMessage();
    $media = AddTicketAttachment::run($message, UploadedFile::fake()->image('photo.jpg', 10, 10), $author);

    $response = $this->actingAs($viewer)->get(route('ticket-attachments.download', $media));

    $response->assertOk();
});

test('a user who cannot view the ticket is denied, even by direct id access', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $stranger = userWithPermissions(Permission::TicketViewOwn);
    $message = ticketMessage(['ticket_id' => ticket(['requester_id' => User::factory()->create()->id])->id]);
    $media = AddTicketAttachment::run($message, UploadedFile::fake()->image('photo.jpg', 10, 10), $author);

    $response = $this->actingAs($stranger)->get(route('ticket-attachments.download', $media));

    $response->assertForbidden();
});

test('serves a sanitized svg, stripping the embedded script before responding', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $viewer = userWithPermissions(Permission::TicketViewAny);
    $message = ticketMessage();
    $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="4"/></svg>';
    $media = AddTicketAttachment::run($message, UploadedFile::fake()->createWithContent('bad.svg', $maliciousSvg), $author);

    expect($media->mime_type)->toBe('image/svg+xml');

    $response = $this->actingAs($viewer)->get(route('ticket-attachments.download', $media));

    $response->assertOk();
    expect($response->getContent())->not->toContain('<script')
        ->not->toContain('alert(1)')
        ->toContain('<circle');
});
