<?php

declare(strict_types=1);

namespace App\Domain\Tags\Models;

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'description', 'estimated_hours', 'documentation_id'])]
class Tag extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<DocumentationPage, $this>
     */
    public function documentationPage(): BelongsTo
    {
        return $this->belongsTo(DocumentationPage::class, 'documentation_id');
    }

    /**
     * @return BelongsToMany<Ticket, $this>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_tag')->withTimestamps();
    }
}
