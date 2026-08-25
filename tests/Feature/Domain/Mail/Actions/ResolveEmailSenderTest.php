<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\ResolveEmailSender;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeClassifiedEmail(string $fromEmail): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => $fromEmail,
        'subject' => 'placeholder',
        'received_at' => now(),
    ]);
}

test('un mittente che corrisponde esattamente a users.email viene identificato', function (): void {
    $user = User::factory()->create(['email' => 'cliente@example.test']);

    $email = makeClassifiedEmail('cliente@example.test');

    $result = ResolveEmailSender::run($email);

    expect($result->user_id)->toBe($user->id)
        ->and($result->status)->toBe(EmailStatus::Classified);
});

test('il match sul mittente è case-insensitive', function (): void {
    $user = User::factory()->create(['email' => 'Cliente@Example.test']);

    $email = makeClassifiedEmail('CLIENTE@EXAMPLE.TEST');

    $result = ResolveEmailSender::run($email);

    expect($result->user_id)->toBe($user->id);
});

test('un mittente con sub-address (plus-addressing) viene identificato rimuovendo il tag', function (): void {
    $user = User::factory()->create(['email' => 'cliente@example.test']);

    $email = makeClassifiedEmail('cliente+notifiche@example.test');

    $result = ResolveEmailSender::run($email);

    expect($result->user_id)->toBe($user->id);
});

test('un match esatto ha priorità sul sub-address quando entrambi esisterebbero', function (): void {
    $exact = User::factory()->create(['email' => 'cliente+tag@example.test']);
    User::factory()->create(['email' => 'cliente@example.test']);

    $email = makeClassifiedEmail('cliente+tag@example.test');

    $result = ResolveEmailSender::run($email);

    expect($result->user_id)->toBe($exact->id);
});

test('un mittente sullo stesso dominio ma senza nessun utente corrispondente non viene mai identificato per solo dominio', function (): void {
    User::factory()->create(['email' => 'collega@example.test']);

    $email = makeClassifiedEmail('sconosciuto@example.test');

    $result = ResolveEmailSender::run($email);

    expect($result->user_id)->toBeNull()
        ->and($result->status)->toBe(EmailStatus::Quarantined);
});

test('un mittente non identificato va in quarantena, mai scartato', function (): void {
    $email = makeClassifiedEmail('fantasma@example.test');

    $result = ResolveEmailSender::run($email);

    expect($result->status)->toBe(EmailStatus::Quarantined)
        ->and($result->user_id)->toBeNull();
});

test('un mittente vuoto va in quarantena', function (): void {
    $email = makeClassifiedEmail('');

    $result = ResolveEmailSender::run($email);

    expect($result->status)->toBe(EmailStatus::Quarantined)
        ->and($result->user_id)->toBeNull();
});
