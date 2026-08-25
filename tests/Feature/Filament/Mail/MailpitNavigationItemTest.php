<?php

declare(strict_types=1);

use App\Filament\Navigation\MailpitNavigationItem;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('the Mailpit item is visible in local with the URL configured', function (): void {
    app()->instance('env', 'local');
    config(['mail_pipeline.mailpit_url' => 'http://localhost:8025']);

    expect(MailpitNavigationItem::isVisible())->toBeTrue();
});

test('the Mailpit item is visible in staging with the URL configured', function (): void {
    app()->instance('env', 'staging');
    config(['mail_pipeline.mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com']);

    expect(MailpitNavigationItem::isVisible())->toBeTrue();
});

test('the Mailpit item is hidden in production even with the URL configured', function (): void {
    app()->instance('env', 'production');
    config(['mail_pipeline.mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com']);

    expect(MailpitNavigationItem::isVisible())->toBeFalse();
});

test('the Mailpit item is hidden in local when the URL is not configured', function (): void {
    app()->instance('env', 'local');
    config(['mail_pipeline.mailpit_url' => '']);

    expect(MailpitNavigationItem::isVisible())->toBeFalse();
});

test('the Mailpit item is hidden in staging when the URL is not configured', function (): void {
    app()->instance('env', 'staging');
    config(['mail_pipeline.mailpit_url' => '']);

    expect(MailpitNavigationItem::isVisible())->toBeFalse();
});

test('the Mailpit item is registered in the Email group as the first navigation item', function (): void {
    $items = Filament::getNavigationItems();
    $mailpit = collect($items)->first(fn (NavigationItem $item): bool => $item->getLabel() === 'Mailpit');

    expect($mailpit)->not->toBeNull();
    expect($mailpit->getGroup())->toBe('Email');
    expect($mailpit->shouldOpenUrlInNewTab())->toBeTrue();
    expect($items[0]->getLabel())->toBe('Mailpit');
});

test('the Mailpit item resolves its URL from config', function (): void {
    config(['mail_pipeline.mailpit_url' => 'http://localhost:8025']);

    expect(MailpitNavigationItem::url())->toBe('http://localhost:8025');

    config(['mail_pipeline.mailpit_url' => '']);

    expect(MailpitNavigationItem::url())->toBeNull();
});
