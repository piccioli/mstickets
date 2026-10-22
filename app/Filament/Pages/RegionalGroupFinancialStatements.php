<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupConsolidatedStatements;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupConsolidatedData;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Bilancio consolidato delle Sezioni del Gruppo Regionale (Fase 10.3, US-1023): selettore dell'esercizio, card del
 * conto economico e dello stato patrimoniale consolidati, copertura (Sezioni con dati / solo PDF / nessun bilancio).
 * Sostituisce la pagina "in arrivo" della 10.2 mantenendo classe e slug (i badge della Dashboard GR puntano qui).
 *
 * Nessuna logica sui dati qui: tutto arriva da {@see CaiRegionalGroupConsolidatedStatements} per la `region`
 * dell'utente. L'esercizio vive nell'URL (`?esercizio=2024`); un anno fuori dai 5 disponibili → esercizio di default.
 *
 * Fuori dalla navigazione (si raggiunge dai badge). Gate: solo un customer con `customer_type = GruppoRegionale`.
 */
class RegionalGroupFinancialStatements extends Page
{
    protected string $view = 'filament.pages.regional-group-financial-statements';

    protected static ?string $title = 'Bilancio consolidato Sezioni';

    protected static ?string $slug = 'gr/bilancio';

    protected static bool $shouldRegisterNavigation = false;

    /** Esercizio richiesto (dall'URL o dal selettore); non validato qui, lo risolve la query. */
    #[Url(as: 'esercizio')]
    public int|string|null $year = null;

    /** @var array<string, CaiRegionalGroupConsolidatedData|null> calcolato una volta per esercizio richiesto */
    private array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::GruppoRegionale;
    }

    /** L'intestazione è resa dalla vista, non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    public function selectYear(int $year): void
    {
        $this->year = $year;
    }

    public function region(): ?Region
    {
        $user = Auth::user();

        return $user instanceof User ? $user->region : null;
    }

    /** Consolidato della regione dell'utente per l'esercizio richiesto, `null` se l'utente non ha regione. */
    public function consolidated(): ?CaiRegionalGroupConsolidatedData
    {
        $year = filter_var($this->year, FILTER_VALIDATE_INT);
        $year = $year === false ? null : $year;
        $key = (string) $year;

        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        $region = $this->region();

        return $this->data[$key] = $region === null ? null : CaiRegionalGroupConsolidatedStatements::forRegion($region, $year);
    }

    public function dashboardUrl(): string
    {
        return RegionalGroupDashboard::getUrl();
    }

    /** "{nome utente GR} · Codice CAI {codice}", senza il codice se la regione non ha Sezioni. */
    public function subtitle(CaiRegionalGroupConsolidatedData $data): string
    {
        $user = Auth::user();

        return collect([$user instanceof User ? $user->name : null, $data->codiceCai === null ? null : 'Codice CAI '.$data->codiceCai])
            ->filter()
            ->implode(' · ');
    }

    /** "Somma di N Sezioni su M" oppure "Nessuna Sezione con dati su M". */
    public static function sumNote(int $count, int $total): string
    {
        return $count === 0
            ? 'Nessuna Sezione con dati su '.$total
            : RegionalGroupDashboard::sumNote($count, $total);
    }

    /** "Include 1 valore da verificare" / "Include N valori da verificare", `null` se nessuno. */
    public static function flaggedNote(int $count): ?string
    {
        return match (true) {
            $count === 0 => null,
            $count === 1 => 'Include 1 valore da verificare',
            default => 'Include '.$count.' valori da verificare',
        };
    }

    /** "su M Sezioni hanno il bilancio con i dati" (al singolare con una sola Sezione). */
    public static function coverageLabel(int $total): string
    {
        return $total === 1
            ? 'su 1 Sezione ha il bilancio con i dati'
            : 'su '.$total.' Sezioni hanno il bilancio con i dati';
    }

    public static function coverageNote(CaiRegionalGroupConsolidatedData $data): string
    {
        if ($data->dataCount === 0) {
            return 'Per l\'esercizio '.$data->year.' nessuna Sezione ha un bilancio con i dati.';
        }

        $missing = $data->sectionsCount - $data->dataCount;

        if ($missing === 0) {
            return 'Tutte le Sezioni hanno fornito il bilancio con i dati.';
        }

        $note = 'I totali consolidati sono parziali: mancano i dati di '.$missing.' '.($missing === 1 ? 'Sezione' : 'Sezioni');

        return match (true) {
            $data->pdfOnlyCount === 0 => $note.'.',
            $missing === 1 => $note.', con il solo PDF.',
            default => $note.', '.$data->pdfOnlyCount.' delle quali con il solo PDF.',
        };
    }
}
