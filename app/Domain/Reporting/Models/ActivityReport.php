<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Models;

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'owner_kind', 'owner_user_id', 'owner_organization_id', 'period_type',
    'year', 'month', 'locale', 'pdf_path', 'pdf_generated_at',
])]
class ActivityReport extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_kind' => ActivityReportOwnerKind::class,
            'period_type' => ActivityReportPeriodType::class,
            'year' => 'integer',
            'month' => 'integer',
            'pdf_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    /**
     * @return BelongsToMany<Ticket, $this>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'activity_report_ticket')->withTimestamps();
    }

    /**
     * Inizio del periodo rendicontato (00:00:00 del primo giorno di mese/anno).
     */
    public function periodStart(): CarbonImmutable
    {
        return match ($this->period_type) {
            ActivityReportPeriodType::Monthly => CarbonImmutable::create($this->year, $this->month, 1)->startOfMonth(),
            ActivityReportPeriodType::Annual => CarbonImmutable::create($this->year, 1, 1)->startOfYear(),
        };
    }

    /**
     * Fine del periodo rendicontato (23:59:59.999999 dell'ultimo giorno di mese/anno).
     */
    public function periodEnd(): CarbonImmutable
    {
        return match ($this->period_type) {
            ActivityReportPeriodType::Monthly => $this->periodStart()->endOfMonth(),
            ActivityReportPeriodType::Annual => $this->periodStart()->endOfYear(),
        };
    }

    /**
     * Nome dell'owner del report (utente o organizzazione, a seconda di owner_kind).
     */
    public function ownerName(): string
    {
        return match ($this->owner_kind) {
            ActivityReportOwnerKind::User => (string) $this->ownerUser?->name,
            ActivityReportOwnerKind::Organization => (string) $this->ownerOrganization?->name,
        };
    }

    /**
     * Etichetta del periodo localizzata in `locale` ("2026" per un report annuale,
     * "Febbraio 2026" per un report mensile).
     */
    public function periodLabel(): string
    {
        return match ($this->period_type) {
            ActivityReportPeriodType::Monthly => Str::ucfirst($this->periodStart()->locale($this->locale)->translatedFormat('F Y')),
            ActivityReportPeriodType::Annual => (string) $this->year,
        };
    }
}
