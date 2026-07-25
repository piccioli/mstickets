<?php

declare(strict_types=1);

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('tags table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('tags', [
        'id', 'name', 'slug', 'description', 'estimated_hours', 'documentation_id',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

test('slug is unique', function (): void {
    Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione']);

    expect(fn () => Tag::create(['name' => 'Manutenzione bis', 'slug' => 'manutenzione']))
        ->toThrow(QueryException::class);
});

test('belongs to a documentation page, nullified when the page is deleted', function (): void {
    $page = DocumentationPage::create(['title' => 'Guida', 'slug' => 'guida', 'body' => 'Contenuto']);
    $tag = Tag::create(['name' => 'Supporto', 'slug' => 'supporto', 'documentation_id' => $page->id]);

    expect($tag->documentationPage->is($page))->toBeTrue();

    $page->forceDelete();

    expect($tag->fresh()->documentation_id)->toBeNull();
});

test('a soft-deleted tag is excluded from default queries', function (): void {
    $tag = Tag::create(['name' => 'Supporto', 'slug' => 'supporto']);

    $tag->delete();

    expect(Tag::count())->toBe(0)
        ->and(Tag::withTrashed()->count())->toBe(1);
});

test('ticket_tag.tag_id has a real foreign key constraint to tags, cascading on delete', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket taggato', 'status_changed_at' => now()]);
    $tag = Tag::create(['name' => 'Supporto', 'slug' => 'supporto']);

    $ticket->tags()->attach($tag);

    expect($ticket->tags()->pluck('tags.id')->all())->toBe([$tag->id]);

    $tag->forceDelete();

    expect($ticket->tags()->count())->toBe(0);
});
