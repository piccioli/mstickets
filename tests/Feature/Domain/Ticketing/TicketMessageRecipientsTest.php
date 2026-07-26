<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recipients are participants plus requester, assignee and tester, deduplicated, excluding the author', function (): void {
    $requester = User::factory()->create();
    $assignee = User::factory()->create();
    $tester = User::factory()->create();
    $participant = User::factory()->create();
    $author = User::factory()->create();

    $t = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'tester_id' => $tester->id,
    ]);
    $t->participants()->attach([$participant->id, $author->id]);

    $recipients = $t->messageRecipients($author);

    expect($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$requester->id, $assignee->id, $tester->id, $participant->id])->sort()->values()->all());
});

test('excludes the author even when the author is also the requester, assignee or tester', function (): void {
    $author = User::factory()->create();
    $participant = User::factory()->create();

    $t = ticket([
        'requester_id' => $author->id,
        'assignee_id' => $author->id,
        'tester_id' => $author->id,
    ]);
    $t->participants()->attach($participant->id);

    $recipients = $t->messageRecipients($author);

    expect($recipients->pluck('id')->all())->toBe([$participant->id]);
});

test('deduplicates a user who is both requester and a participant', function (): void {
    $author = User::factory()->create();
    $requester = User::factory()->create();

    $t = ticket(['requester_id' => $requester->id]);
    $t->participants()->attach($requester->id);

    $recipients = $t->messageRecipients($author);

    expect($recipients->pluck('id')->all())->toBe([$requester->id]);
});

test('returns an empty collection when there is nobody else to notify', function (): void {
    $author = User::factory()->create();
    $t = ticket();

    expect($t->messageRecipients($author))->toBeEmpty();
});
