<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages\Support;

use App\Domain\Documentation\Models\DocumentationPage;
use Illuminate\Http\UploadedFile;

/**
 * Riusata da CreateDocumentationPage/EditDocumentationPage (US-405): i campi
 * `documents`/`images` del form usano `->storeFiles(false)`, quindi arrivano
 * come `UploadedFile` grezzi da salvare sulle media collection dedicate, mai
 * come attributi del model.
 */
trait AttachesDocumentationMedia
{
    /**
     * `$files` arriva dallo stato grezzo del form Livewire (`array<int, mixed>`):
     * finché il file non è stato effettivamente ricevuto dal browser, Filament
     * può valorizzare lo stato con stringhe/altri tipi, mai solo `UploadedFile`
     * — da qui il controllo `instanceof` prima di operare sul file.
     *
     * @param  array<int, mixed>  $files
     */
    private static function attachDocumentationMedia(DocumentationPage $page, array $files, string $collection): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $page->addMedia($file)->usingName($file->getClientOriginalName())->toMediaCollection($collection);
        }
    }
}
