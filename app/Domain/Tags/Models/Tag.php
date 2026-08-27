<?php

declare(strict_types=1);

namespace App\Domain\Tags\Models;

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Ticketing\Enums\TicketStatus;
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

    public function workedMinutes(): int
    {
        return (int) $this->tickets()->sum('worked_minutes');
    }

    public function sal(): ?float
    {
        $estimatedHours = $this->estimated_hours === null ? null : (float) $this->estimated_hours;

        if ($estimatedHours === null || $estimatedHours === 0.0) {
            return null;
        }

        $workedHours = $this->workedMinutes() / 60;

        return round($workedHours / $estimatedHours * 100, 2);
    }

    public function isClosed(): bool
    {
        if ($this->tickets()->doesntExist()) {
            return false;
        }

        return $this->tickets()
            ->whereNotIn('status', [TicketStatus::Released, TicketStatus::Done])
            ->doesntExist();
    }
}
