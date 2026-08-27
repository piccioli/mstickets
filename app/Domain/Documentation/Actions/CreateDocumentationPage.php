<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Actions;

use App\Domain\Documentation\Events\DocumentationPageCreated;
use App\Domain\Documentation\Models\DocumentationPage;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per creare una pagina di documentazione (§6.4.2,
 * US-405): genera lo slug da `title` (mai un campo del form) ed emette
 * `DocumentationPageCreated`, che innesca la creazione del Tag collegato — mai
 * un hook Eloquent sul model.
 */
final class CreateDocumentationPage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function run(array $attributes): DocumentationPage
    {
        return DB::transaction(function () use ($attributes): DocumentationPage {
            $title = (string) $attributes['title'];

            $page = DocumentationPage::create([
                ...$attributes,
                'slug' => DocumentationPage::uniqueSlug($title),
            ]);

            event(new DocumentationPageCreated($page));

            return $page;
        });
    }
}
