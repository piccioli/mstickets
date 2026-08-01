<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Import\Inspect\Analyzers\OrphanForeignKeyAnalyzer;
use App\Import\Models\ImportRun;
use App\Import\Validation\WorkedHoursDeviationAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDOException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Confronta lo stato v2 (frutto di una o più esecuzioni di v1:import) con il dump v1
 * in db_legacy (§11.7 del PRD, US-216) e produce un report di validazione in
 * storage/app/import/, stesso pattern di v1:inspect (US-008). Pensato come gate CI
 * (§11.7): esce con status di errore se un qualunque controllo di integrità fallisce
 * o se i conteggi delle entità con id conservato non coincidono con l'atteso.
 *
 * Il confronto dei derivati (ore lavorate, totali fundraising) e la sezione dei
 * compromessi applicati sono invece SOLO informativi: documentano un'assunzione
 * operativa (tolleranza ±5% per Q6) o un fatto da rivedere col committente al
 * checkpoint di fine fase (US-219), non fanno fallire il comando da soli.
 */
final class V1ValidateCommand extends Command
{
    protected $signature = 'v1:validate';

    protected $description = 'Confronta i dati importati (v2) con il dump v1 (db_legacy) e produce un report di validazione in storage/app/import/.';

    private const CONNECTION = 'legacy';

    private const HOURS_TOLERANCE = 0.05;

    /** @var array<int, array{label:string, v1_table:string, v2_table:string}> Entità con id v1 conservato (§14 del PRD). */
    private const ENTITY_MAPPINGS = [
        ['label' => 'users', 'v1_table' => 'users', 'v2_table' => 'users'],
        ['label' => 'organizations', 'v1_table' => 'organizations', 'v2_table' => 'organizations'],
        ['label' => 'documentation_pages', 'v1_table' => 'documentations', 'v2_table' => 'documentation_pages'],
        ['label' => 'tags', 'v1_table' => 'tags', 'v2_table' => 'tags'],
        ['label' => 'tickets', 'v1_table' => 'stories', 'v2_table' => 'tickets'],
        ['label' => 'activity_reports', 'v1_table' => 'activity_reports', 'v2_table' => 'activity_reports'],
        ['label' => 'fundraising_opportunities', 'v1_table' => 'fundraising_opportunities', 'v2_table' => 'fundraising_opportunities'],
        ['label' => 'fundraising_projects', 'v1_table' => 'fundraising_projects', 'v2_table' => 'fundraising_projects'],
    ];

    /** @var array<int, array{label:string, child_table:string, child_column:string, parent_table:string, parent_column:string}> */
    private const FK_CHECKS = [
        ['label' => 'tickets.requester_id → users.id', 'child_table' => 'tickets', 'child_column' => 'requester_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'tickets.assignee_id → users.id', 'child_table' => 'tickets', 'child_column' => 'assignee_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'tickets.tester_id → users.id', 'child_table' => 'tickets', 'child_column' => 'tester_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'tickets.parent_id → tickets.id', 'child_table' => 'tickets', 'child_column' => 'parent_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'ticket_logs.ticket_id → tickets.id', 'child_table' => 'ticket_logs', 'child_column' => 'ticket_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'ticket_logs.user_id → users.id', 'child_table' => 'ticket_logs', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'ticket_tag.ticket_id → tickets.id', 'child_table' => 'ticket_tag', 'child_column' => 'ticket_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'ticket_tag.tag_id → tags.id', 'child_table' => 'ticket_tag', 'child_column' => 'tag_id', 'parent_table' => 'tags', 'parent_column' => 'id'],
        ['label' => 'ticket_participants.ticket_id → tickets.id', 'child_table' => 'ticket_participants', 'child_column' => 'ticket_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'ticket_participants.user_id → users.id', 'child_table' => 'ticket_participants', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'ticket_views.ticket_id → tickets.id', 'child_table' => 'ticket_views', 'child_column' => 'ticket_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'ticket_views.user_id → users.id', 'child_table' => 'ticket_views', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'ticket_messages.ticket_id → tickets.id', 'child_table' => 'ticket_messages', 'child_column' => 'ticket_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'ticket_messages.author_id → users.id', 'child_table' => 'ticket_messages', 'child_column' => 'author_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'organization_user.organization_id → organizations.id', 'child_table' => 'organization_user', 'child_column' => 'organization_id', 'parent_table' => 'organizations', 'parent_column' => 'id'],
        ['label' => 'organization_user.user_id → users.id', 'child_table' => 'organization_user', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'activity_reports.owner_user_id → users.id', 'child_table' => 'activity_reports', 'child_column' => 'owner_user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'activity_reports.owner_organization_id → organizations.id', 'child_table' => 'activity_reports', 'child_column' => 'owner_organization_id', 'parent_table' => 'organizations', 'parent_column' => 'id'],
        ['label' => 'activity_report_ticket.activity_report_id → activity_reports.id', 'child_table' => 'activity_report_ticket', 'child_column' => 'activity_report_id', 'parent_table' => 'activity_reports', 'parent_column' => 'id'],
        ['label' => 'activity_report_ticket.ticket_id → tickets.id', 'child_table' => 'activity_report_ticket', 'child_column' => 'ticket_id', 'parent_table' => 'tickets', 'parent_column' => 'id'],
        ['label' => 'fundraising_projects.fundraising_opportunity_id → fundraising_opportunities.id', 'child_table' => 'fundraising_projects', 'child_column' => 'fundraising_opportunity_id', 'parent_table' => 'fundraising_opportunities', 'parent_column' => 'id'],
        ['label' => 'fundraising_project_partners.fundraising_project_id → fundraising_projects.id', 'child_table' => 'fundraising_project_partners', 'child_column' => 'fundraising_project_id', 'parent_table' => 'fundraising_projects', 'parent_column' => 'id'],
        ['label' => 'fundraising_project_partners.user_id → users.id', 'child_table' => 'fundraising_project_partners', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
        ['label' => 'fundraising_evaluation_scores.fundraising_opportunity_id → fundraising_opportunities.id', 'child_table' => 'fundraising_evaluation_scores', 'child_column' => 'fundraising_opportunity_id', 'parent_table' => 'fundraising_opportunities', 'parent_column' => 'id'],
        ['label' => 'tags.documentation_id → documentation_pages.id', 'child_table' => 'tags', 'child_column' => 'documentation_id', 'parent_table' => 'documentation_pages', 'parent_column' => 'id'],
    ];

    /** @var array<int, string> */
    private array $lines = [];

    private bool $integrityFailed = false;

    public function handle(): int
    {
        if (! $this->legacyConnectionIsReachable()) {
            $this->error('Impossibile connettersi alla connessione "legacy" (db_legacy).');
            $this->line('Assicurati che il servizio sia avviato con `make etl-up` e che il dump sia caricato con `bin/load-v1-dump path/to/dump.sql`.');

            return self::FAILURE;
        }

        $this->heading('Report v1:validate');
        $this->addLine('Generato: '.now()->toDateTimeString());
        $this->addLine('');
        $this->addLine('Confronto tra la connessione `legacy` (servizio `db_legacy`, sola lettura) e lo schema v2 corrente.');

        $this->reportEntityCounts();
        $this->reportIntegrityChecks();
        $this->reportWorkedHours();
        $this->reportFundraisingTotals();
        $this->reportCompromises();
        $this->reportIdempotencyNote();

        $path = $this->writeReport();

        if ($this->integrityFailed) {
            $this->error("Validazione FALLITA: uno o più controlli di integrità/conteggi non superati. Report salvato in storage/app/{$path}");

            return self::FAILURE;
        }

        $this->info("Validazione superata. Report salvato in storage/app/{$path}");

        return self::SUCCESS;
    }

    private function legacyConnectionIsReachable(): bool
    {
        try {
            DB::connection(self::CONNECTION)->getPdo();
        } catch (PDOException) {
            return false;
        }

        return true;
    }

    private function legacyHasTable(string $table): bool
    {
        return Schema::connection(self::CONNECTION)->hasTable($table);
    }

    private function reportEntityCounts(): void
    {
        $this->heading('Conteggio righe: v1 vs v2 (entità con id conservato)');

        $rows = [];

        foreach (self::ENTITY_MAPPINGS as $mapping) {
            if (! $this->legacyHasTable($mapping['v1_table'])) {
                $rows[] = [$mapping['label'], 'N/D', (string) DB::table($mapping['v2_table'])->count(), '-', 'SALTATO (tabella v1 assente nel dump)'];

                continue;
            }

            $v1Count = DB::connection(self::CONNECTION)->table($mapping['v1_table'])->count();
            $v2Count = DB::table($mapping['v2_table'])->count();
            $delta = $v1Count - $v2Count;
            $matches = $delta === 0;

            if (! $matches) {
                $this->integrityFailed = true;
            }

            $rows[] = [$mapping['label'], (string) $v1Count, (string) $v2Count, (string) $delta, $matches ? 'OK' : 'MISMATCH'];
        }

        if ($this->legacyHasTable('story_logs') && Schema::hasTable('ticket_logs')) {
            $watchCount = $this->countWatchOnlyStoryLogs();
            $v1Count = DB::connection(self::CONNECTION)->table('story_logs')->count();
            $v2Count = DB::table('ticket_logs')->count();
            $expected = $v1Count - $watchCount;
            $matches = $v2Count === $expected;

            if (! $matches) {
                $this->integrityFailed = true;
            }

            $rows[] = [
                'ticket_logs (atteso = story_logs − watch)',
                (string) $v1Count,
                (string) $v2Count,
                (string) ($v1Count - $v2Count),
                $matches
                    ? "OK (atteso {$expected}, watch escluse: {$watchCount})"
                    : "MISMATCH (atteso {$expected}, watch escluse: {$watchCount})",
            ];
        }

        $this->tableRows(['Entità', 'v1', 'v2', 'Δ (v1-v2)', 'Esito'], $rows);
    }

    private function countWatchOnlyStoryLogs(): int
    {
        $count = 0;

        foreach (DB::connection(self::CONNECTION)->table('story_logs')->pluck('changes') as $raw) {
            $changes = json_decode((string) $raw, true);

            if (is_array($changes) && array_keys($changes) === ['watch']) {
                $count++;
            }
        }

        return $count;
    }

    private function reportIntegrityChecks(): void
    {
        $this->heading('Controlli di integrità (atteso: zero)');

        $this->reportForeignKeyChecks();
        $this->reportEnumChecks();
        $this->reportUniquenessChecks();
        $this->reportDataQualityChecks();
        $this->reportMediaOnDisk();
    }

    private function reportForeignKeyChecks(): void
    {
        $rows = [];

        foreach (self::FK_CHECKS as $check) {
            if (! Schema::hasTable($check['child_table']) || ! Schema::hasTable($check['parent_table'])) {
                continue;
            }

            $childValues = DB::table($check['child_table'])->pluck($check['child_column'])->all();
            $parentIds = DB::table($check['parent_table'])->pluck($check['parent_column'])->all();
            $analysis = OrphanForeignKeyAnalyzer::analyze($childValues, $parentIds);

            if ($analysis['orphan_count'] > 0) {
                $this->integrityFailed = true;
            }

            $rows[] = [
                $check['label'],
                (string) $analysis['checked'],
                (string) $analysis['orphan_count'],
                $analysis['orphan_values'] === [] ? '-' : implode(', ', $analysis['orphan_values']),
            ];
        }

        $this->tableRows(['FK', 'Valorizzati', 'Orfani', 'Esempi'], $rows);
    }

    private function reportEnumChecks(): void
    {
        $this->addLine('');
        $this->addLine('**Valori enum fuori catalogo (atteso: zero)**');

        $enumChecks = [
            'tickets.type' => array_column(TicketType::cases(), 'value'),
            'tickets.priority' => array_column(TicketPriority::cases(), 'value'),
            'tickets.status' => array_column(TicketStatus::cases(), 'value'),
        ];

        foreach ($enumChecks as $columnLabel => $validValues) {
            [$table, $column] = explode('.', $columnLabel);

            $distinctValues = DB::table($table)->select($column)->distinct()->pluck($column)->filter()->values()->all();
            $invalid = array_values(array_diff($distinctValues, $validValues));

            if ($invalid !== []) {
                $this->integrityFailed = true;
            }

            $this->addLine("- {$columnLabel}: ".($invalid === []
                ? '0 valori fuori catalogo'
                : count($invalid).' valori fuori catalogo ('.implode(', ', $invalid).')'));
        }
    }

    private function reportUniquenessChecks(): void
    {
        $this->addLine('');
        $this->addLine('**Violazioni di unicità (atteso: zero)**');

        $this->reportDuplicateCount('users (email case-insensitive)', 'users', 'lower(email)');
        $this->reportDuplicateCount('tags.slug', 'tags', 'slug');
        $this->reportDuplicateCount('documentation_pages.slug', 'documentation_pages', 'slug');
    }

    private function reportDuplicateCount(string $label, string $table, string $expression): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $duplicates = DB::table($table)
            ->selectRaw("{$expression} as dedup_key")
            ->groupBy('dedup_key')
            ->havingRaw('count(*) > 1')
            ->count();

        if ($duplicates > 0) {
            $this->integrityFailed = true;
        }

        $this->addLine("- {$label}: {$duplicates} gruppi duplicati");
    }

    private function reportDataQualityChecks(): void
    {
        $this->addLine('');
        $this->addLine('**Controlli aggiuntivi (atteso: zero)**');

        $ticketsWithoutRequester = DB::table('tickets')->whereNull('requester_id')->count();

        if ($ticketsWithoutRequester > 0) {
            $this->integrityFailed = true;
        }

        $this->addLine("- Ticket senza richiedente (requester_id null): {$ticketsWithoutRequester}");

        if (Schema::hasTable('ticket_messages')) {
            $messagesWithoutTicket = DB::table('ticket_messages')
                ->whereNotIn('ticket_id', DB::table('tickets')->select('id'))
                ->count();

            if ($messagesWithoutTicket > 0) {
                $this->integrityFailed = true;
            }

            $this->addLine("- Messaggi senza ticket valido: {$messagesWithoutTicket}");
        }
    }

    private function reportMediaOnDisk(): void
    {
        $this->addLine('');
        $this->addLine('**Media mancanti sul disco (atteso: zero)**');

        if (! Schema::hasTable('media')) {
            $this->addLine('- Tabella media assente: controllo saltato.');

            return;
        }

        $checked = 0;
        $missing = 0;

        foreach (Media::query()->cursor() as $media) {
            $checked++;

            if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
                $missing++;
            }
        }

        if ($missing > 0) {
            $this->integrityFailed = true;
        }

        $this->addLine("- Media verificati: {$checked}, mancanti su disco: {$missing}.");
    }

    private function reportWorkedHours(): void
    {
        $this->heading('Ore lavorate per ticket: v1 vs v2 (Q6 del PRD, tolleranza ±5% per ticket — assunzione operativa da confermare col committente in US-219)');

        if (! $this->legacyHasTable('stories') || ! Schema::connection(self::CONNECTION)->hasColumn('stories', 'hours')) {
            $this->addLine('- `stories.hours` non presente nello schema legacy: confronto non applicabile.');

            return;
        }

        if (! Schema::hasTable('tickets')) {
            $this->addLine('- Tabella tickets assente in v2: confronto non applicabile.');

            return;
        }

        $v1Hours = DB::connection(self::CONNECTION)->table('stories')->pluck('hours', 'id');
        $v2Minutes = DB::table('tickets')->pluck('worked_minutes', 'id');

        $rows = [];

        foreach ($v1Hours as $id => $hours) {
            if (! $v2Minutes->has($id)) {
                continue;
            }

            $rows[] = [
                'id' => (int) $id,
                'v1_hours' => $hours === null ? null : (float) $hours,
                'v2_minutes' => (int) $v2Minutes[$id],
            ];
        }

        $analysis = WorkedHoursDeviationAnalyzer::analyze($rows, self::HOURS_TOLERANCE);

        $this->addLine("- Ticket confrontabili (v1 hours > 0): {$analysis['compared']} (esclusi senza ore v1: {$analysis['skipped_no_v1_hours']})");
        $this->addLine("- Entro tolleranza ±5%: {$analysis['within_tolerance']}");
        $this->addLine('- Oltre tolleranza: '.count($analysis['beyond_tolerance']));
        $this->addLine("- Scostamento percentuale: min {$analysis['min_deviation_percent']}%, media {$analysis['avg_deviation_percent']}%, max {$analysis['max_deviation_percent']}%");

        if ($analysis['beyond_tolerance'] !== []) {
            $this->addLine('- Ticket oltre tolleranza:');

            foreach ($analysis['beyond_tolerance'] as $entry) {
                $this->addLine("  - ticket #{$entry['id']}: v1 {$entry['v1_hours']}h, v2 {$entry['v2_hours']}h, scostamento {$entry['deviation_percent']}%");
            }
        }
    }

    private function reportFundraisingTotals(): void
    {
        $this->heading('Totali di valutazione fundraising: v1 (ricalcolato dai punteggi grezzi) vs v2 (§6.6.2, devono coincidere esattamente)');

        if (! $this->legacyHasTable('fundraising_opportunities')) {
            $this->addLine('- fundraising_opportunities assente nel dump legacy: confronto non applicabile.');

            return;
        }

        $scoreColumns = [];

        foreach (FundraisingEvaluationCriterion::cases() as $criterion) {
            $column = "evaluation_{$criterion->value}_score";

            if (Schema::connection(self::CONNECTION)->hasColumn('fundraising_opportunities', $column)) {
                $scoreColumns[] = $column;
            }
        }

        if ($scoreColumns === []) {
            $this->addLine('- Nessuna colonna evaluation_*_score nello schema legacy (griglia di valutazione §6.6.2 mai usata in v1, vedi FundraisingScoresStage/US-213): confronto non applicabile, nulla da conciliare.');

            return;
        }

        $rows = DB::connection(self::CONNECTION)->table('fundraising_opportunities')->select(['id', ...$scoreColumns])->get();

        $compared = 0;
        $mismatches = 0;
        $details = [];

        foreach ($rows as $row) {
            $positive = 0;
            $negative = 0;

            foreach ($scoreColumns as $column) {
                $value = $row->{$column} ?? null;

                if ($value === null) {
                    continue;
                }

                $value = (int) $value;

                if ($value >= 0) {
                    $positive += $value;
                } else {
                    $negative += $value;
                }
            }

            $v1Total = $positive + $negative;

            $opportunity = DB::table('fundraising_opportunities')->where('id', $row->id)->first();

            if ($opportunity === null || $opportunity->evaluation_total === null) {
                continue;
            }

            $compared++;

            if ((int) $opportunity->evaluation_total !== $v1Total) {
                $mismatches++;
                $details[] = "  - opportunità #{$row->id}: v1 {$v1Total}, v2 {$opportunity->evaluation_total} (probabile clamp di un punteggio fuori range in FundraisingScoresStage)";
            }
        }

        $this->addLine("- Opportunità confrontate: {$compared}, discrepanze: {$mismatches}.");

        foreach ($details as $detail) {
            $this->addLine($detail);
        }
    }

    private function reportCompromises(): void
    {
        $this->heading("Compromessi applicati (dai warning dell'ultima esecuzione di v1:import)");

        $importRun = ImportRun::query()->whereNotNull('finished_at')->orderByDesc('started_at')->first();

        if ($importRun === null) {
            $this->addLine('- Nessuna esecuzione di v1:import trovata: eseguire prima l\'import per popolare questa sezione.');

            return;
        }

        $startedAt = $importRun->started_at->toDateTimeString();
        $this->addLine("- Riferimento: import_runs#{$importRun->id}, avviato {$startedAt}, stato {$importRun->status->value}.");

        /** @var array<string, array{warnings?: array<int, string>}> $stages */
        $stages = $importRun->stages ?? [];
        $hasWarnings = false;

        foreach ($stages as $name => $result) {
            $warnings = $result['warnings'] ?? [];

            if ($warnings === []) {
                continue;
            }

            $hasWarnings = true;
            $this->addLine("- **{$name}**:");

            foreach ($warnings as $warning) {
                $this->addLine("  - {$warning}");
            }
        }

        if (! $hasWarnings) {
            $this->addLine('- Nessun compromesso segnalato dagli stage.');
        }
    }

    private function reportIdempotencyNote(): void
    {
        $this->heading('Verifica di idempotenza');
        $this->addLine("- Verificata da un test dedicato (`tests/Feature/Console/V1ImportPipelineIdempotencyTest.php`): esegue `v1:import` due volte consecutive sullo stesso dump e verifica che la seconda esecuzione produca zero righe create/aggiornate (solo \"saltate\") su ogni stage registrato in config('import.stages').");
        $this->addLine('- Non ripetuta qui a runtime: v1:validate confronta lo stato v2 già esistente (frutto della/e esecuzione/i di v1:import già effettuate) con il dump legacy, senza lanciare un nuovo import (comando di sola lettura).');
    }

    private function heading(string $title): void
    {
        $this->lines[] = '';
        $this->lines[] = "## {$title}";
        $this->lines[] = '';
    }

    private function addLine(string $text): void
    {
        $this->lines[] = $text;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string>>  $rows
     */
    private function tableRows(array $header, array $rows): void
    {
        $this->lines[] = '| '.implode(' | ', $header).' |';
        $this->lines[] = '| '.implode(' | ', array_fill(0, count($header), '---')).' |';

        foreach ($rows as $row) {
            $this->lines[] = '| '.implode(' | ', $row).' |';
        }
    }

    private function writeReport(): string
    {
        $timestamp = now()->format('Ymd_His');
        $path = "import/validate-{$timestamp}.md";

        $this->appDisk()->put($path, implode("\n", $this->lines)."\n");

        return $path;
    }

    /**
     * Disco nominato "import-reports" (config/filesystems.php), radicato su
     * storage/app come richiesto dal PRD per questo report: a differenza del
     * Storage::build() ad-hoc di V1InspectCommand::appDisk(), un disco nominato è
     * intercettabile da Storage::fake('import-reports') nei test (vedi CLAUDE.md,
     * stesso principio già applicato al disco "legacy-media", US-211).
     */
    private function appDisk(): Filesystem
    {
        return Storage::disk('import-reports');
    }
}
