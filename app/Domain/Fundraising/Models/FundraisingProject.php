<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Models;

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'fundraising_opportunity_id', 'lead_user_id', 'created_by', 'responsible_user_id',
    'description', 'status', 'requested_amount', 'approved_amount', 'submitted_at', 'decided_at',
])]
class FundraisingProject extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FundraisingProjectStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'submitted_at' => 'date',
            'decided_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<FundraisingOpportunity, $this>
     */
    public function fundraisingOpportunity(): BelongsTo
    {
        return $this->belongsTo(FundraisingOpportunity::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function leadUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'fundraising_project_partners')->withTimestamps();
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Partner con ruolo customer (fix del bug v1: il filtro andava sulla pivot
     * `model_has_roles` via la relazione `roles()` di Spatie, mai su una colonna JSON).
     *
     * @return BelongsToMany<User, $this>
     */
    public function partnerCustomers(): BelongsToMany
    {
        return $this->partners()->whereHas(
            'roles',
            fn (Builder $query): Builder => $query->where('name', UserRole::Customer->value),
        );
    }

    /**
     * "Coinvolti" (§6.6.3): capofila, partner, responsabile o creatore.
     *
     * @param  Builder<FundraisingProject>  $query
     * @return Builder<FundraisingProject>
     */
    public function scopeInvolving(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('lead_user_id', $user->id)
                ->orWhere('responsible_user_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('partners', fn (Builder $query): Builder => $query->whereKey($user->id));
        });
    }

    /**
     * "Coinvolto" per la vista cliente (§6.6.4): SOLO capofila o partner. A
     * differenza di {@see scopeInvolving()} (uso interno staff, §6.6.3),
     * responsabile/creatore sono esclusi di proposito: sono ruoli interni
     * allo staff, mai attribuibili a un customer nel flusso applicativo.
     *
     * @param  Builder<FundraisingProject>  $query
     * @return Builder<FundraisingProject>
     */
    public function scopeInvolvingAsCustomer(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('lead_user_id', $user->id)
                ->orWhereHas('partners', fn (Builder $query): Builder => $query->whereKey($user->id));
        });
    }
}
