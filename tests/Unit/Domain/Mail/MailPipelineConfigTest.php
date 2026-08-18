<?php

declare(strict_types=1);
use App\Domain\Mail\Enums\ImapFolderRole;

test('the imap account config has the shape expected by ClientManager::make()', function (): void {
    expect(array_keys(config('mail_pipeline.imap')))->toBe([
        'host', 'port', 'encryption', 'validate_cert', 'username', 'password',
    ]);
});

test('every ImapFolderRole has a configured folder name', function (): void {
    $folders = config('mail_pipeline.folders');

    foreach (ImapFolderRole::cases() as $role) {
        expect($folders)->toHaveKey($role->value);
        expect($folders[$role->value])->toBeString()->not->toBeEmpty();
    }
});

test('the default fetch limit is a positive integer', function (): void {
    expect(config('mail_pipeline.fetch.default_limit'))->toBeInt()->toBeGreaterThan(0);
});

test('the anti-loop rate limit thresholds are positive integers', function (): void {
    expect(config('mail_pipeline.rate_limit.max_per_hour'))->toBeInt()->toBeGreaterThan(0);
    expect(config('mail_pipeline.rate_limit.max_per_day'))->toBeInt()->toBeGreaterThan(0);
});

test('the staff notification group is parsed from a comma-separated env value', function (): void {
    config(['mail_pipeline.staff_notification_group' => array_values(array_filter(array_map(
        trim(...),
        explode(',', 'staff-a@orchestrator.local, staff-b@orchestrator.local'),
    )))]);

    expect(config('mail_pipeline.staff_notification_group'))->toBe([
        'staff-a@orchestrator.local',
        'staff-b@orchestrator.local',
    ]);
});
