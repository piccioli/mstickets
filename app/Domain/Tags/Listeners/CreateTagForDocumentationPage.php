<?php

declare(strict_types=1);

namespace App\Domain\Tags\Listeners;

use App\Domain\Documentation\Events\DocumentationPageCreated;
use App\Domain\Tags\Models\Tag;

/**
 * Reagisce a `DocumentationPageCreated` creando il Tag "Documentation: <titolo>"
 * collegato alla pagina (`tags.documentation_id`, §6.4.2, US-405) — mai un hook
 * `booted()`/`creating()` sul model `DocumentationPage`, correzione esplicita
 * rispetto al v1.
 */
final class CreateTagForDocumentationPage
{
    public function handle(DocumentationPageCreated $event): void
    {
        $name = "Documentation: {$event->page->title}";

        Tag::create([
            'name' => $name,
            'slug' => Tag::uniqueSlug($name),
            'documentation_id' => $event->page->id,
        ]);
    }
}
