<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Models;

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Support\TicketMessageSanitizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'slug', 'body', 'category', 'pdf_path', 'pdf_generated_at'])]
class DocumentationPage extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentationCategory::class,
            'pdf_generated_at' => 'datetime',
        ];
    }

    /**
     * Stessa sanificazione allowlist di `ticket_messages.body_html` (§6.4.1 del PRD):
     * riusa TicketMessageSanitizer, nessuna logica di sanificazione duplicata qui.
     *
     * @return Attribute<string, string>
     */
    protected function body(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => TicketMessageSanitizer::sanitize($value),
        );
    }

    /**
     * Disco privato dedicato (§6.4.1 del PRD, US-404): mai `public`, altrimenti un
     * allegato di una pagina `category=internal` sarebbe raggiungibile via URL diretto
     * indipendentemente dalla DocumentationPagePolicy.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')->useDisk('documentation-attachments');
        $this->addMediaCollection('images')->useDisk('documentation-attachments');
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * `documentation.view.customer` e `documentation.view.internal` sono due gate
     * indipendenti, non una gerarchia (§9.4): un utente vede una categoria solo se ha
     * il permesso specifico per quella categoria, anche se ne ha uno solo dei due. Una
     * pagina internal non è raggiungibile da un utente customer nemmeno conoscendone
     * l'id (stesso principio già applicato da TicketMessage::scopeVisibleTo()).
     *
     * @param  Builder<DocumentationPage>  $query
     * @return Builder<DocumentationPage>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $visibleCategories = array_filter([
            $user->can(Permission::DocumentationViewCustomer) ? DocumentationCategory::Customer : null,
            $user->can(Permission::DocumentationViewInternal) ? DocumentationCategory::Internal : null,
        ]);

        if ($visibleCategories === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('category', $visibleCategories);
    }
}
