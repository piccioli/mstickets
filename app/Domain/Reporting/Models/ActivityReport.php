<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Models;

use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Ticketing\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
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
     * Rimuove il PDF generato dal disco quando il record viene eliminato (§6.5.3
     * del PRD, US-409): nessun file orfano su storage. Hook tecnico di pulizia
     * risorsa (non un evento di dominio), stesso principio già applicato da
     * spatie/medialibrary alle proprie media al delete del model.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $report): void {
            if ($report->pdf_path !== null) {
                Storage::disk(config('reporting.pdf.disk'))->delete($report->pdf_path);
            }
        });
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

    /**
     * Nome proposto per il PDF scaricato (§6.5.3 del PRD, US-409): sigla
     * piattaforma + owner + periodo, es. "MS-cai-sezione-milano-2026-02.pdf".
     * Mai il percorso su disco (`pdf_path`, sempre basato sull'id — vedi
     * {@see GenerateActivityReportPdf}).
     */
    public function pdfDownloadFilename(): string
    {
        $periodSlug = match ($this->period_type) {
            ActivityReportPeriodType::Monthly => sprintf('%d-%02d', $this->year, $this->month),
            ActivityReportPeriodType::Annual => (string) $this->year,
        };

        return sprintf(
            '%s-%s-%s.pdf',
            config('reporting.platform_acronym'),
            Str::slug($this->ownerName()),
            $periodSlug,
        );
    }

    /**
     * Il report appartiene davvero a `$user` (§9.4: un permesso "own" autorizza
     * solo i propri report, mai quelli di un altro owner anche via id manipolato
     * sull'URL) — utente owner diretto, oppure organizzazione owner di cui
     * `$user` è membro.
     */
    public function isOwnedBy(User $user): bool
    {
        return match ($this->owner_kind) {
            ActivityReportOwnerKind::User => $this->owner_user_id === $user->id,
            ActivityReportOwnerKind::Organization => $this->owner_organization_id !== null
                && $user->organizations()->whereKey($this->owner_organization_id)->exists(),
        };
    }
}
