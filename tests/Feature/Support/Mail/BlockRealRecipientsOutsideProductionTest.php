<?php

declare(strict_types=1);

use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    config([
        'mail.default' => 'array',
        'orchestrator.anonymization.mail_test_domains' => ['test.orchestrator.invalid'],
    ]);
});

function sentArrayMessagesCount(): int
{
    /** @var ArrayTransport $transport */
    $transport = Mail::mailer('array')->getSymfonyTransport();

    return $transport->messages()->count();
}

test('an email to a real, non-allowlisted domain is blocked outside production', function (): void {
    Mail::mailer('array')->raw('Corpo del messaggio', function ($message): void {
        $message->to('cliente.vero@gmail.com')->subject('Notifica ticket');
    });

    expect(sentArrayMessagesCount())->toBe(0);
});

test('an email to an allowlisted test domain is delivered outside production', function (): void {
    Mail::mailer('array')->raw('Corpo del messaggio', function ($message): void {
        $message->to('mario.rossi.42@test.orchestrator.invalid')->subject('Notifica ticket');
    });

    expect(sentArrayMessagesCount())->toBe(1);
});

test('a real recipient hidden in cc/bcc is also blocked outside production', function (): void {
    Mail::mailer('array')->raw('Corpo del messaggio', function ($message): void {
        $message->to('mario.rossi.42@test.orchestrator.invalid')
            ->cc('cliente.vero@gmail.com')
            ->subject('Notifica ticket');
    });

    expect(sentArrayMessagesCount())->toBe(0);
});

test('the guard is bypassed entirely in production, real recipients included', function (): void {
    app()->instance('env', 'production');

    Mail::mailer('array')->raw('Corpo del messaggio', function ($message): void {
        $message->to('cliente.vero@gmail.com')->subject('Notifica ticket');
    });

    expect(sentArrayMessagesCount())->toBe(1);
});
