<?php

declare(strict_types=1);

namespace App\Domain\Tags\Actions;

use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Unico punto di ingresso per trasformare un ticket in una commessa (US-402,
 * §6.3): crea il Tag con slug univoco, precompila `estimated_hours` dal ticket
 * sorgente se non altrimenti specificato e collega il ticket via
 * `syncWithoutDetaching` (mai `attach()` diretto, per non violare il vincolo
 * unique `ticket_tag` se l'azione fosse invocata più volte sullo stesso ticket).
 */
final class CreateTagFromTicket
{
    public static function run(Ticket $ticket, string $name, ?float $estimatedHours = null): Tag
    {
        return DB::transaction(function () use ($ticket, $name, $estimatedHours): Tag {
            $tag = Tag::create([
                'name' => $name,
                'slug' => self::uniqueSlug($name),
                'estimated_hours' => $estimatedHours ?? ($ticket->estimated_hours !== null ? (float) $ticket->estimated_hours : null),
            ]);

            $tag->tickets()->syncWithoutDetaching([$ticket->id]);

            return $tag;
        });
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'n-a' : $base;
        $slug = $base;
        $suffix = 1;

        while (Tag::withTrashed()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
