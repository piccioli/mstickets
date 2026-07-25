<?php

declare(strict_types=1);

test('every scheduled automation feature flag defaults to false', function (): void {
    $flags = config('orchestrator.features');

    expect($flags)->toBeArray()->not->toBeEmpty();

    foreach ($flags as $key => $value) {
        expect($value)->toBeBool()->and($value)->toBeFalse();
    }
});

test('the feature flag catalog matches §10.2 of the PRD', function (): void {
    expect(array_keys(config('orchestrator.features')))->toBe([
        'tickets_progress_to_todo',
        'tickets_auto_close_released',
        'tickets_close_scrum',
        'tickets_restore_waiting',
        'tickets_waiting_reminders',
        'tickets_archive_scrum',
        'mail_fetch_inbound',
        'mail_retry_failed',
        'timetracking_aggregate',
        'reports_monthly',
        'mail_digest',
        'tickets_idle_developer_notice',
    ]);
});
