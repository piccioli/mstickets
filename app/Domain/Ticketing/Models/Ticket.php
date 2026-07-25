<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parent_id', 'title', 'description', 'status', 'previous_status', 'status_changed_at',
    'type', 'priority', 'requester_id', 'assignee_id', 'tester_id', 'fundraising_project_id',
    'waiting_reason', 'problem_reason', 'estimated_hours', 'worked_minutes', 'staging_url',
    'production_url', 'released_at', 'done_at',
])]
class Ticket extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'previous_status' => TicketStatus::class,
            'type' => TicketType::class,
            'priority' => TicketPriority::class,
            'status_changed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'worked_minutes' => 'integer',
            'released_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * @return HasMany<TicketLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class);
    }

    /**
     * @return HasMany<TicketView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(TicketView::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_participants')->withTimestamps();
    }

    /**
     * @return HasMany<TicketWorkLog, $this>
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(TicketWorkLog::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag')->withTimestamps();
    }
}
