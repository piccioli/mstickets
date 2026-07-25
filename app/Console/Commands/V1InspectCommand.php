<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Import\Inspect\Analyzers\ChangesKeyAnalyzer;
use App\Import\Inspect\Analyzers\CustomerRequestAnalyzer;
use App\Import\Inspect\Analyzers\DuplicateEmailAnalyzer;
use App\Import\Inspect\Analyzers\OrphanForeignKeyAnalyzer;
use App\Import\Inspect\Analyzers\RoleValueAnalyzer;
use App\Import\Inspect\Analyzers\StatusTimestampAnalyzer;
use App\Import\Inspect\Analyzers\StoryHierarchyAnalyzer;
use App\Import\Inspect\Analyzers\TaggableAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDOException;
use Throwable;

/**
 * Ispeziona il dump v1 caricato nel servizio db_legacy (§0.1 punto 5, §11.2 del
 * PRD) prima che lo schema v2 venga finalizzato. Legge in sola lettura dalla
 * connessione "legacy" e produce un report markdown in storage/app/import/.
 */
final class V1InspectCommand extends Command
{
    protected $signature = 'v1:inspect';

    protected $description = 'Ispeziona il dump v1 in db_legacy e produce un report pre-migrazione in storage/app/import/.';

    private const CONNECTION = 'legacy';

    /** @var array<int, string> */
    private array $lines = [];

    public function handle(): int
    {
        if (! $this->legacyConnectionIsReachable()) {
            $this->error('Impossibile connettersi alla connessione "legacy" (db_legacy).');
            $this->line('Assicurati che il servizio sia avviato con `make etl-up` e che il dump sia caricato con `bin/load-v1-dump path/to/dump.sql`.');

            return self::FAILURE;
        }

        $tables = $this->discoverTables();

        $this->heading('Report v1:inspect');
        $this->addLine('Generato: '.now()->toDateTimeString());
        $this->addLine('');
        $this->addLine('Fonte: connessione `legacy` (servizio `db_legacy`, sola lettura). Tabelle rilevate: '.count($tables).'.');

        $this->reportRowCounts($tables);
        $this->reportStoriesDistinctValues($tables);
        $this->reportUserRoles($tables);
        $this->reportDuplicateEmails($tables);
        $this->reportCustomerRequests($tables);
        $this->reportStoryLogsChanges($tables);
        $this->reportStoryHierarchy($tables);
        $this->reportStatusTimestamps($tables);
        $this->reportStoryParticipants($tables);
        $this->reportTaggables($tables);
        $this->reportMedia($tables);
        $this->reportOrphanForeignKeys($tables);

        $path = $this->writeReport();

        $this->info("Report salvato in storage/app/{$path}");

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

    /**
     * @return array<int, string>
     */
    private function discoverTables(): array
    {
        $rows = DB::connection(self::CONNECTION)->select(
            "select table_name from information_schema.tables where table_schema = 'public' and table_type = 'BASE TABLE' order by table_name",
        );

        return array_map(static fn (object $row): string => (string) $row->table_name, $rows);
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function tableExists(array $tables, string $table): bool
    {
        return in_array($table, $tables, true);
    }

    /**
     * @return array<int, mixed>
     */
    private function column(string $table, string $column): array
    {
        return DB::connection(self::CONNECTION)->table($table)->pluck($column)->all();
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportRowCounts(array $tables): void
    {
        $this->heading('Conteggio righe per tabella');
        $this->tableRows(['Tabella', 'Righe'], array_map(
            fn (string $table): array => [$table, (string) $this->rowCount($table)],
            $tables,
        ));
    }

    private function rowCount(string $table): int
    {
        try {
            return (int) DB::connection(self::CONNECTION)->table($table)->count();
        } catch (Throwable) {
            return -1;
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportStoriesDistinctValues(array $tables): void
    {
        if (! $this->tableExists($tables, 'stories')) {
            return;
        }

        $this->heading('stories — valori distinti di status/type/priority');

        foreach (['status', 'type', 'priority'] as $column) {
            $counts = DB::connection(self::CONNECTION)
                ->table('stories')
                ->selectRaw("{$column}, count(*) as total")
                ->groupBy($column)
                ->orderByDesc('total')
                ->get();

            $this->addLine("- **{$column}**:");

            foreach ($counts as $row) {
                $value = $row->{$column} ?? '(null)';
                $this->addLine("  - `{$value}`: {$row->total}");
            }
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportUserRoles(array $tables): void
    {
        if (! $this->tableExists($tables, 'users')) {
            return;
        }

        $this->heading('users.roles — formati e valori distinti');

        $analysis = RoleValueAnalyzer::analyze($this->column('users', 'roles'));

        $this->addLine("- Totale utenti: {$analysis['total']}");
        $this->addLine("- roles null/vuoto: {$analysis['null_or_empty_count']}");
        $this->addLine("- roles in formato JSON (array): {$analysis['json_array_count']}");
        $this->addLine("- roles in formato non-JSON (scalare): {$analysis['scalar_count']}");
        $this->addLine('- Valori grezzi distinti:');

        foreach ($analysis['distinct_raw'] as $raw => $count) {
            $this->addLine("  - `{$raw}`: {$count}");
        }

        $this->addLine('- Ruoli distinti (dopo parsing JSON):');

        foreach ($analysis['distinct_roles'] as $role => $count) {
            $this->addLine("  - `{$role}`: {$count}");
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportDuplicateEmails(array $tables): void
    {
        if (! $this->tableExists($tables, 'users')) {
            return;
        }

        $this->heading('users — email duplicate a meno del case');

        $rows = DB::connection(self::CONNECTION)->table('users')->select('id', 'email')->get()
            ->map(static fn (object $row): array => ['id' => $row->id, 'email' => $row->email])
            ->all();

        $duplicates = DuplicateEmailAnalyzer::analyze($rows);

        if ($duplicates === []) {
            $this->addLine('- Nessuna email duplicata a meno del case.');

            return;
        }

        foreach ($duplicates as $group) {
            $ids = implode(', ', $group['ids']);
            $examples = implode(', ', $group['examples']);
            $this->addLine("- `{$group['email_lower']}`: {$group['count']} utenti (id: {$ids}) — varianti: {$examples}");
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportCustomerRequests(array $tables): void
    {
        if (! $this->tableExists($tables, 'stories')) {
            return;
        }

        $this->heading('stories.customer_request — messaggi distinti');

        $rows = DB::connection(self::CONNECTION)->table('stories')->select('id', 'customer_request')->get()
            ->map(static fn (object $row): array => ['id' => $row->id, 'customer_request' => $row->customer_request])
            ->all();

        $analysis = CustomerRequestAnalyzer::analyze($rows);

        $this->addLine("- stories con customer_request non vuota: {$analysis['non_empty_count']}");
        $this->addLine("- di cui parsabili in più messaggi distinti: {$analysis['multi_message_count']}");
        $this->addLine('- Esempi campione:');

        foreach ($analysis['samples'] as $sample) {
            $this->addLine("  - story #{$sample['id']} ({$sample['message_count']} messaggi):");

            foreach ($sample['messages'] as $message) {
                $excerpt = mb_strlen($message) > 120 ? mb_substr($message, 0, 120).'…' : $message;
                $this->addLine("    - {$excerpt}");
            }
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportStoryLogsChanges(array $tables): void
    {
        if (! $this->tableExists($tables, 'story_logs')) {
            return;
        }

        $this->heading('story_logs.changes — interpretabilità e chiavi');

        $analysis = ChangesKeyAnalyzer::analyze($this->column('story_logs', 'changes'));

        $this->addLine("- Totale story_logs: {$analysis['total']}");
        $this->addLine("- changes interpretabile (JSON valido): {$analysis['interpretable_count']}");
        $this->addLine("- changes non interpretabile (null/vuoto/JSON invalido): {$analysis['non_interpretable_count']}");
        $this->addLine('- Distribuzione chiavi:');

        $keyDistribution = $analysis['key_distribution'];
        arsort($keyDistribution);

        foreach ($keyDistribution as $key => $count) {
            $this->addLine("  - `{$key}`: {$count}");
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportStoryHierarchy(array $tables): void
    {
        if (! $this->tableExists($tables, 'stories') || ! $this->tableExists($tables, 'story_story')) {
            return;
        }

        $this->heading('stories.parent_id vs story_story');

        $stories = DB::connection(self::CONNECTION)->table('stories')->select('id', 'parent_id')->get()
            ->map(static fn (object $row): array => ['id' => $row->id, 'parent_id' => $row->parent_id])
            ->all();

        $storyStoryRows = DB::connection(self::CONNECTION)->table('story_story')->select('parent_id', 'child_id')->get()
            ->map(static fn (object $row): array => ['parent_id' => $row->parent_id, 'child_id' => $row->child_id])
            ->all();

        $analysis = StoryHierarchyAnalyzer::analyze($stories, $storyStoryRows);

        $this->addLine("- Righe story_story: {$analysis['story_story_rows']}");
        $this->addLine("- Righe story_story NON riflesse in stories.parent_id: {$analysis['story_story_not_reflected_in_parent_id']}");
        $this->addLine("- stories.parent_id valorizzato NON riflesso in story_story: {$analysis['parent_id_not_reflected_in_story_story']}");
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportStatusTimestamps(array $tables): void
    {
        if (! $this->tableExists($tables, 'stories')) {
            return;
        }

        $this->heading('stories — timestamp mancanti per stato done/released');

        $doneRows = DB::connection(self::CONNECTION)->table('stories')->select('id', 'status', 'done_at')->get()
            ->map(static fn (object $row): array => ['id' => $row->id, 'status' => $row->status, 'timestamp' => $row->done_at])
            ->all();

        $releasedRows = DB::connection(self::CONNECTION)->table('stories')->select('id', 'status', 'released_at')->get()
            ->map(static fn (object $row): array => ['id' => $row->id, 'status' => $row->status, 'timestamp' => $row->released_at])
            ->all();

        $done = StatusTimestampAnalyzer::analyze($doneRows, 'done');
        $released = StatusTimestampAnalyzer::analyze($releasedRows, 'released');

        $this->addLine("- status=done: {$done['checked']} righe, done_at null in {$done['missing_count']} (esempi id: ".implode(', ', $done['missing_ids']).')');
        $this->addLine("- status=released: {$released['checked']} righe, released_at null in {$released['missing_count']} (esempi id: ".implode(', ', $released['missing_ids']).')');
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportStoryParticipants(array $tables): void
    {
        if (! $this->tableExists($tables, 'story_participants')) {
            return;
        }

        $this->heading('story_participants');
        $this->addLine('- Righe: '.$this->rowCount('story_participants'));
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportTaggables(array $tables): void
    {
        $this->heading('tags/taggables — taggable_type diverso da Documentation');

        foreach (['tags', 'taggables'] as $table) {
            if (! $this->tableExists($tables, $table)) {
                continue;
            }

            $analysis = TaggableAnalyzer::analyze($this->column($table, 'taggable_type'));

            $this->addLine("- **{$table}** (totale {$analysis['total']}, non-Documentation: {$analysis['non_documentation_count']}):");

            foreach ($analysis['by_type'] as $type => $count) {
                $this->addLine("  - `{$type}`: {$count}");
            }
        }
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportMedia(array $tables): void
    {
        if (! $this->tableExists($tables, 'media')) {
            return;
        }

        $this->heading('media — conteggio, dimensione, presenza su disco');

        $count = $this->rowCount('media');
        $totalSize = (int) DB::connection(self::CONNECTION)->table('media')->sum('size');
        $avgSize = $count > 0 ? (int) round($totalSize / $count) : 0;

        $this->addLine("- Righe: {$count}");
        $this->addLine('- Dimensione totale: '.number_format($totalSize / 1_048_576, 2).' MB');
        $this->addLine('- Dimensione media: '.number_format($avgSize / 1024, 2).' KB');

        if (! $this->appDisk()->exists('v1-media')) {
            $this->addLine('- Verifica presenza file su disco: NON eseguita — nessuno storage v1 (`storage/app/v1-media`) disponibile in questo ambiente, solo il dump SQL. Da rifare quando il committente fornirà anche i file allegati.');

            return;
        }

        $sampled = DB::connection(self::CONNECTION)->table('media')->select('disk', 'file_name')->limit(200)->get();
        $present = 0;
        $missing = 0;

        foreach ($sampled as $row) {
            $exists = $this->appDisk()->exists("v1-media/{$row->file_name}");
            $exists ? $present++ : $missing++;
        }

        $this->addLine("- Campione verificato: {$sampled->count()} file, presenti: {$present}, mancanti: {$missing}.");
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportOrphanForeignKeys(array $tables): void
    {
        $this->heading('FK orfane');

        /** @var array<int, array{label:string, child_table:string, child_column:string, parent_table:string, parent_column:string}> $relations */
        $relations = [
            ['label' => 'stories.user_id → users.id', 'child_table' => 'stories', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
            ['label' => 'stories.creator_id → users.id', 'child_table' => 'stories', 'child_column' => 'creator_id', 'parent_table' => 'users', 'parent_column' => 'id'],
            ['label' => 'stories.tester_id → users.id', 'child_table' => 'stories', 'child_column' => 'tester_id', 'parent_table' => 'users', 'parent_column' => 'id'],
            ['label' => 'stories.epic_id → epics.id', 'child_table' => 'stories', 'child_column' => 'epic_id', 'parent_table' => 'epics', 'parent_column' => 'id'],
            ['label' => 'stories.project_id → projects.id', 'child_table' => 'stories', 'child_column' => 'project_id', 'parent_table' => 'projects', 'parent_column' => 'id'],
            ['label' => 'stories.fundraising_project_id → fundraising_projects.id', 'child_table' => 'stories', 'child_column' => 'fundraising_project_id', 'parent_table' => 'fundraising_projects', 'parent_column' => 'id'],
            ['label' => 'stories.parent_id → stories.id', 'child_table' => 'stories', 'child_column' => 'parent_id', 'parent_table' => 'stories', 'parent_column' => 'id'],
            ['label' => 'story_logs.story_id → stories.id', 'child_table' => 'story_logs', 'child_column' => 'story_id', 'parent_table' => 'stories', 'parent_column' => 'id'],
            ['label' => 'story_logs.user_id → users.id', 'child_table' => 'story_logs', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
            ['label' => 'story_story.parent_id → stories.id', 'child_table' => 'story_story', 'child_column' => 'parent_id', 'parent_table' => 'stories', 'parent_column' => 'id'],
            ['label' => 'story_story.child_id → stories.id', 'child_table' => 'story_story', 'child_column' => 'child_id', 'parent_table' => 'stories', 'parent_column' => 'id'],
            ['label' => 'story_participants.story_id → stories.id', 'child_table' => 'story_participants', 'child_column' => 'story_id', 'parent_table' => 'stories', 'parent_column' => 'id'],
            ['label' => 'story_participants.user_id → users.id', 'child_table' => 'story_participants', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
            ['label' => 'taggables.tag_id → tags.id', 'child_table' => 'taggables', 'child_column' => 'tag_id', 'parent_table' => 'tags', 'parent_column' => 'id'],
            ['label' => 'organization_user.organization_id → organizations.id', 'child_table' => 'organization_user', 'child_column' => 'organization_id', 'parent_table' => 'organizations', 'parent_column' => 'id'],
            ['label' => 'organization_user.user_id → users.id', 'child_table' => 'organization_user', 'child_column' => 'user_id', 'parent_table' => 'users', 'parent_column' => 'id'],
            ['label' => 'activity_reports.organization_id → organizations.id', 'child_table' => 'activity_reports', 'child_column' => 'organization_id', 'parent_table' => 'organizations', 'parent_column' => 'id'],
            ['label' => 'activity_reports.customer_id → customers.id', 'child_table' => 'activity_reports', 'child_column' => 'customer_id', 'parent_table' => 'customers', 'parent_column' => 'id'],
            ['label' => 'activity_report_story.activity_report_id → activity_reports.id', 'child_table' => 'activity_report_story', 'child_column' => 'activity_report_id', 'parent_table' => 'activity_reports', 'parent_column' => 'id'],
            ['label' => 'activity_report_story.story_id → stories.id', 'child_table' => 'activity_report_story', 'child_column' => 'story_id', 'parent_table' => 'stories', 'parent_column' => 'id'],
        ];

        foreach ($relations as $relation) {
            if (! $this->tableExists($tables, $relation['child_table']) || ! $this->tableExists($tables, $relation['parent_table'])) {
                $this->addLine("- {$relation['label']}: SALTATO (tabella non presente in questo dump)");

                continue;
            }

            $childValues = $this->column($relation['child_table'], $relation['child_column']);
            $parentIds = $this->column($relation['parent_table'], $relation['parent_column']);

            $analysis = OrphanForeignKeyAnalyzer::analyze($childValues, $parentIds);

            $orphanExamples = $analysis['orphan_values'] === [] ? '' : ' (esempi: '.implode(', ', $analysis['orphan_values']).')';
            $this->addLine("- {$relation['label']}: {$analysis['checked']} valorizzati, {$analysis['orphan_count']} orfani{$orphanExamples}");
        }
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
        $path = "import/inspect-{$timestamp}.md";

        $this->appDisk()->put($path, implode("\n", $this->lines)."\n");

        return $path;
    }

    /**
     * The "local" disk root is storage/app/private (Laravel 11+ default), but
     * this report must live directly under storage/app/import as required by
     * the PRD: build a disk rooted at storage/app instead of using "local".
     */
    private function appDisk(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);
    }
}
