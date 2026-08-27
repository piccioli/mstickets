<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Actions;

use App\Domain\Documentation\Events\DocumentationPageContentChanged;
use App\Domain\Documentation\Events\DocumentationPageRenamed;
use App\Domain\Documentation\Models\DocumentationPage;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per aggiornare una pagina di documentazione (§6.4.2,
 * US-405). Emette `DocumentationPageRenamed` solo quando `title` è
 * effettivamente cambiato rispetto al valore persistito — mai su ogni save,
 * altrimenti il listener rinominerebbe il Tag collegato anche quando il
 * titolo non è cambiato. Emette separatamente `DocumentationPageContentChanged`
 * (§6.4.3, US-406) quando `title` O `body` sono cambiati, per rigenerare il PDF
 * anche su una modifica del solo corpo, che non tocca il Tag.
 */
final class UpdateDocumentationPage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function run(DocumentationPage $page, array $attributes): DocumentationPage
    {
        return DB::transaction(function () use ($page, $attributes): DocumentationPage {
            $oldTitle = $page->title;
            $oldBody = $page->body;

            $page->fill($attributes);
            $page->save();

            $newTitle = $page->title;

            if ($newTitle !== $oldTitle) {
                event(new DocumentationPageRenamed($page, $oldTitle, $newTitle));
            }

            if ($newTitle !== $oldTitle || $page->body !== $oldBody) {
                event(new DocumentationPageContentChanged($page));
            }

            return $page;
        });
    }
}
