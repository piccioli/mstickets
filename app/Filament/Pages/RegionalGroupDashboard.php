<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupDashboardData;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Dashboard V2 del Gruppo Regionale (Fase 10.2, US-1014): intestazione col nome dell'utente GR, codice CAI del GR e
 * regione, poi tre righe di badge metrica (`x-cai.metric-badge`) — bilancio proprio del GR (sempre "Non
 * disponibile" in questa feature), bilancio consolidato delle Sezioni, e Sezioni/Soci/Rifugi/Sentieri/Tappe SICAI —
 * ciascuno col link alla propria pagina ({@see RegionalGroupComingSoonPage}, "Sezioni" →
 * {@see CustomerRegionalSectionsDashboard}). I quattro badge di bilancio aprono {@see RegionalGroupFinancialStatements}:
 * quelli consolidati sull'esercizio della card (US-1025), quelli del bilancio proprio senza esercizio.
 *
 * Nessuna logica sui dati qui: le metriche arrivano da {@see CaiRegionalGroupDashboardMetrics} per la `region`
 * dell'utente. Senza regione la pagina mostra il nome, un avviso e tutti i badge "Non disponibile".
 *
 * Gate: solo un customer con `customer_type = GruppoRegionale`.
 */
class RegionalGroupDashboard extends Page
{
    protected string $view = 'filament.pages.regional-group-dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'gr/dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'GR';

    /** In testa al gruppo "GR", prima di {@see CustomerDashboard} (-2). */
    protected static ?int $navigationSort = -3;

    private bool $metricsResolved = false;

    private ?CaiRegionalGroupDashboardData $metrics = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::GruppoRegionale;
    }

    /** L'intestazione (nome del GR) è resa dalla vista, non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    public function region(): ?Region
    {
        $user = Auth::user();

        return $user instanceof User ? $user->region : null;
    }

    /** Metriche della regione dell'utente, `null` se l'utente non ha regione. Calcolate una sola volta per richiesta. */
    public function metrics(): ?CaiRegionalGroupDashboardData
    {
        if ($this->metricsResolved) {
            return $this->metrics;
        }

        $this->metricsResolved = true;
        $region = $this->region();

        return $this->metrics = $region === null ? null : CaiRegionalGroupDashboardMetrics::forRegion($region);
    }

    public function userName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : '';
    }

    /** "Codice CAI {codice GR} · {regione}", senza il codice se la regione non ha Sezioni. */
    public function subtitle(Region $region, CaiRegionalGroupDashboardData $data): string
    {
        return collect([$data->codiceCai === null ? null : 'Codice CAI '.$data->codiceCai, $region->label()])
            ->filter()
            ->implode(' · ');
    }

    /** Pagina del consolidato Sezioni sull'esercizio indicato (`?esercizio=`), senza parametro se la card non ne ha. */
    public static function financialStatementsUrl(?int $year = null): string
    {
        return RegionalGroupFinancialStatements::getUrl($year === null ? [] : ['esercizio' => $year]);
    }

    /** "Somma di {N} Sezioni" (o "Sezione" al singolare), con " su {M}" se indicato. */
    public static function sumNote(int $count, ?int $total = null): string
    {
        $note = 'Somma di '.$count.' '.($count === 1 ? 'Sezione' : 'Sezioni');

        return $total === null ? $note : $note.' su '.$total;
    }
}
