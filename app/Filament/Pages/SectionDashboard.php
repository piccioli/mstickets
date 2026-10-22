<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Queries\CaiSectionDashboardMetrics;
use App\Domain\CaiDirectory\Support\CaiSectionDashboardData;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Dashboard V2 della Sezione (Fase 10.1, US-1004): intestazione con nome/codice/regione della Sezione e sei badge
 * metrica (`x-cai.metric-badge`) — conto economico e stato patrimoniale in riga 1, soci/rifugi/sentieri/tappe SICAI
 * in riga 2 — ciascuno col link alla propria pagina di approfondimento ({@see SectionComingSoonPage}).
 *
 * Nessuna logica sui dati qui: le metriche arrivano da {@see CaiSectionDashboardMetrics}. L'utente è collegato
 * alla propria `CaiSection` (o, in mancanza, alla `CaiSubsection`) per `user_id`, come {@see CustomerDashboard}.
 * Senza né l'una né l'altra la pagina mostra il nome dell'utente, un avviso e tutti i badge "Non disponibile".
 *
 * Gate: solo un customer con `customer_type = Sezione` (stesso idioma delle pagine "in arrivo").
 */
class SectionDashboard extends Page
{
    protected string $view = 'filament.pages.section-dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'sezione/dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Area cliente';

    /** In testa al gruppo "Area cliente", prima di {@see CustomerDashboard} (-2). */
    protected static ?int $navigationSort = -3;

    private bool $metricsResolved = false;

    private ?CaiSectionDashboardData $metrics = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::Sezione;
    }

    /** L'intestazione (nome della Sezione) è resa dalla vista, non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    /**
     * Metriche della Sezione collegata all'utente, altrimenti della sua Sottosezione; `null` se nessuna delle due.
     * Calcolate una sola volta per richiesta.
     */
    public function metrics(): ?CaiSectionDashboardData
    {
        if ($this->metricsResolved) {
            return $this->metrics;
        }

        $this->metricsResolved = true;
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->metrics = null;
        }

        $section = CaiSection::query()->where('user_id', $user->id)->first();

        if ($section !== null) {
            return $this->metrics = CaiSectionDashboardMetrics::forSection($section);
        }

        $subsection = CaiSubsection::query()->with('section')->where('user_id', $user->id)->first();

        return $this->metrics = $subsection === null ? null : CaiSectionDashboardMetrics::forSubsection($subsection);
    }

    public function userName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : '';
    }

    /** "Codice CAI {codice} · {regione}", senza la regione se manca. */
    public function subtitle(CaiSectionDashboardData $data): string
    {
        return collect(['Codice CAI '.$data->codiceCai, $data->region])->filter()->implode(' · ');
    }
}
