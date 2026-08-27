<?php

declare(strict_types=1);

namespace App\Domain\Tags\Listeners;

use App\Domain\Documentation\Events\DocumentationPageRenamed;
use App\Domain\Tags\Models\Tag;

/**
 * Reagisce a `DocumentationPageRenamed` rinominando il Tag già collegato alla
 * pagina, mantenendo la stessa riga (`tags.documentation_id`, §6.4.2, US-405):
 * mai la creazione di un nuovo Tag. Nessun-op se la pagina non ha (ancora) un
 * Tag collegato — non dovrebbe accadere in pratica (ogni pagina ne ottiene uno
 * alla creazione via {@see CreateTagForDocumentationPage}), ma un evento
 * rilanciato manualmente su una pagina senza Tag non deve fallire.
 */
final class RenameTagForDocumentationPage
{
    public function handle(DocumentationPageRenamed $event): void
    {
        $tag = $event->page->tags()->first();

        if ($tag === null) {
            return;
        }

        $newName = "Documentation: {$event->newTitle}";

        $tag->update([
            'name' => $newName,
            'slug' => Tag::uniqueSlug($newName, $tag->id),
        ]);
    }
}
