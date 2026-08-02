<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Support\Doctor\Checks\SystemUserCheck;
use Database\Seeders\Concerns\SeedsActivityReports;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Popola l'ambiente pubblico di collaudo (UAT) con un dataset realistico e deterministico,
 * pensato per essere descritto uno-a-uno nel PDF di collaudo formale (Task 3/8). Eseguito ad
 * ogni deploy UAT come `migrate:fresh --seed --class=UatSeeder` (non tramite `DatabaseSeeder`,
 * quindi richiama da sé `RolePermissionSeeder`): titoli/nomi sono testo scritto a mano, stabile
 * tra un deploy e l'altro, e mai frutto di generazione casuale. Nessuna chiamata a `fake()`: `fakerphp/faker`
 * è una dipendenza `require-dev`, assente nell'immagine UAT costruita con `composer install
 * --no-dev` (vedi `docker/uat/Dockerfile`), quindi qualunque uso di `fake()` qui farebbe
 * crashare il seeder ad ogni deploy reale. Non eseguibile in produzione.
 */
class UatSeeder extends Seeder
{
    use SeedsActivityReports;

    private const PASSWORD = 'password';

    /**
     * @var array<string, array{email: string, password: string}>
     */
    private array $credentials = [];

    /**
     * Copia nullable del comando console: la proprietà ereditata `Seeder::$command` è
     * documentata come sempre presente, ma non lo è quando il seeder gira fuori da
     * `php artisan db:seed` (es. istanziato direttamente nei test). Con
     * `treatPhpDocTypesAsCertain` (default true), Larastan tratterebbe `$this->command?->...`
     * come sempre non-null; questa proprietà nostra, dichiarata `?Command`, evita il problema.
     */
    private ?Command $console = null;

    public function setCommand(Command $command): static
    {
        $this->console = $command;

        return parent::setCommand($command);
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('UatSeeder non può essere eseguito in produzione.');
        }

        // Eseguito standalone via `--class=UatSeeder`, senza passare dalla catena di
        // `DatabaseSeeder`: i ruoli/permessi vanno materializzati qui.
        $this->call(RolePermissionSeeder::class);

        app(SystemUserCheck::class)->run();

        $roleUsers = $this->seedRoleUsers();
        $organizations = $this->seedOrganizations();

        if (! Ticket::query()->exists()) {
            $this->seedDocumentationPages();
            $tags = $this->seedTags();
            $tickets = $this->seedTickets($roleUsers, $tags);
            $this->seedActivityReports($roleUsers, $organizations, $tickets);
            $this->seedFundraising($roleUsers);
        } else {
            $this->console?->info('UatSeeder: ticket/tag/documentazione/report/fundraising già presenti, salto la generazione.');
        }

        $this->printCredentials();
    }

    /**
     * @return array<string, User>
     */
    private function seedRoleUsers(): array
    {
        $names = [
            UserRole::Admin->value => 'Amministratore Collaudo',
            UserRole::Developer->value => 'Sviluppatore Collaudo',
            UserRole::Manager->value => 'Manager Collaudo',
            UserRole::Customer->value => 'Socio CAI Collaudo',
            UserRole::Fundraising->value => 'Referente Fundraising Collaudo',
        ];

        $users = [];

        foreach (UserRole::cases() as $role) {
            $email = "{$role->value}@orchestrator.local";

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $names[$role->value],
                    'password' => self::PASSWORD,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRole($role->value);

            $users[$role->value] = $user;
            $this->credentials[$names[$role->value]] = ['email' => $email, 'password' => self::PASSWORD];
        }

        return $users;
    }

    /**
     * @return list<Organization>
     */
    private function seedOrganizations(): array
    {
        $names = ['CAI Sezione di Aosta', 'CAI Sezione di Trento'];

        return array_map(
            static fn (string $name): Organization => Organization::query()->firstOrCreate(['name' => $name], ['locale' => 'it']),
            $names,
        );
    }

    /**
     * @return list<DocumentationPage>
     */
    private function seedDocumentationPages(): array
    {
        $pages = [
            ['title' => 'Guida rapida al portale ticket per i soci CAI', 'category' => DocumentationCategory::Customer],
            ['title' => 'FAQ tesseramento e rinnovo della quota associativa', 'category' => DocumentationCategory::Customer],
            ['title' => 'Come segnalare un problema tecnico dal portale', 'category' => DocumentationCategory::Customer],
            ['title' => 'Procedura interna di validazione dei ticket in collaudo', 'category' => DocumentationCategory::Internal],
            ['title' => 'Checklist di rilascio per l\'ambiente di collaudo UAT', 'category' => DocumentationCategory::Internal],
        ];

        return array_map(static function (array $page): DocumentationPage {
            $slug = Str::slug($page['title']);

            return DocumentationPage::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'body' => "Contenuto di collaudo UAT per «{$page['title']}».",
                    'category' => $page['category'],
                ],
            );
        }, $pages);
    }

    /**
     * @return list<Tag>
     */
    private function seedTags(): array
    {
        $names = [
            'Frontend', 'Backend', 'Database', 'API', 'UI/UX',
            'Performance', 'Sicurezza', 'Bug critico', 'Manutenzione', 'Nuova funzionalità',
        ];

        return array_map(static function (string $name, int $index): Tag {
            $slug = Str::slug($name);

            return Tag::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'estimated_hours' => ($index + 1) * 1.5,
                ],
            );
        }, $names, array_keys($names));
    }

    /**
     * Titoli scritti a mano, riconoscibili nel dominio CAI/montagna, raggruppati per tipo di
     * ticket: pensati per essere citati uno-a-uno nel PDF di collaudo, mai generati a caso.
     *
     * @return array<string, list<string>>
     */
    private function ticketTitlesByType(): array
    {
        return [
            TicketType::Bug->value => [
                'Il pulsante «Rinnova tessera» non risponde su Safari mobile',
                'Errore 500 aprendo il dettaglio di un ticket con più allegati',
                "L'importo del bollettino MAV non arrotonda correttamente le quote sezionali",
                "Il calendario delle escursioni mostra date errate con l'ora legale",
                'La ricerca socio per codice fiscale restituisce risultati duplicati',
                'Il PDF della tessera stampa il logo sovrapposto al QR code',
                'Il filtro per sezione territoriale non si azzera dopo il logout',
                "L'invio della newsletter blocca la coda se un indirizzo email è malformato",
                'Il contatore rinnovi tessere non si aggiorna dopo un bonifico',
                "L'elenco rifugi non carica le foto su connessione lenta",
            ],
            TicketType::Feature->value => [
                "Aggiungere l'export CSV dell'elenco iscritti al corso di escursionismo",
                'Notifica al socio 30 giorni prima della scadenza tessera',
                'Filtro avanzato per cercare rifugi per fascia altimetrica',
                'Firma digitale del modulo di iscrizione minori accompagnati',
                'Dashboard riepilogativa delle quote raccolte per sezione',
                'Allegare il certificato medico direttamente al profilo socio',
                'Integrazione con il calendario per le uscite del gruppo escursionismo',
                'Modulo di richiesta rimborso spese per gli accompagnatori',
                'Vista mappa interattiva dei sentieri segnalati dalla sezione',
                'Badge digitale sostitutivo della tessera cartacea in rifugio',
            ],
            TicketType::Helpdesk->value => [
                "Il socio non riceve l'email di conferma rinnovo tessera",
                'Richiesta di reset password per un accompagnatore in trasferta',
                "Come modificare l'indirizzo di residenza di un socio già iscritto",
                'Il socio non trova più la ricevuta del bollettino dell\'anno scorso',
                'Assistenza per l\'accesso da un nuovo dispositivo',
                'Richiesta di duplicato tessera per smarrimento',
                'Il socio chiede come trasferirsi da una sezione a un\'altra',
                'Segnalazione di un doppio addebito sulla quota associativa',
                'Richiesta chiarimenti sulla copertura assicurativa in escursione',
                'Il referente sezione chiede l\'elenco aggiornato dei soci minorenni',
            ],
            TicketType::Scrum->value => [
                'Sprint planning: revisione backlog modulo tesseramento',
                'Refactoring del servizio di calcolo quote per il nuovo anno sociale',
                'Aggiornamento dipendenze in vista del prossimo rilascio',
                'Retrospettiva sprint: automatizzare i test del modulo rendicontazione',
                'Spike tecnico: migrazione del disco allegati su storage esterno',
                'Debito tecnico: consolidare le policy duplicate del modulo ticketing',
                'Preparazione demo di fine sprint per il modulo fundraising',
                'Pianificazione capacità del team per il collaudo UAT',
                'Code review incrociata sulle Action del modulo Ticketing',
                'Chiusura sprint: verifica indici mancanti su Postgres',
            ],
        ];
    }

    /**
     * @param  array<string, User>  $roleUsers
     * @param  list<Tag>  $tags
     * @return list<Ticket>
     */
    private function seedTickets(array $roleUsers, array $tags): array
    {
        $customer = $roleUsers[UserRole::Customer->value];
        $developer = $roleUsers[UserRole::Developer->value];
        $manager = $roleUsers[UserRole::Manager->value];

        $statuses = TicketStatus::cases();
        $types = TicketType::cases();
        $priorities = TicketPriority::cases();
        $titlesByType = $this->ticketTitlesByType();

        $tickets = [];

        for ($i = 0; $i < 40; $i++) {
            $status = $statuses[$i % count($statuses)];
            $type = $types[$i % count($types)];
            $priority = $priorities[$i % count($priorities)];
            $assignee = $i % 2 === 0 ? $developer : $manager;
            $statusChangedAt = now()->subDays(40 - $i);

            $titlesForType = $titlesByType[$type->value];
            $title = $titlesForType[intdiv($i, count($types)) % count($titlesForType)];

            $attributes = [
                'title' => $title,
                'description' => "Caso di collaudo UAT #{$i}: {$title}.",
                'status' => $status,
                'type' => $type,
                'priority' => $priority,
                'status_changed_at' => $statusChangedAt,
                'requester_id' => $customer->id,
                'assignee_id' => $assignee->id,
                'estimated_hours' => (($i % 8) + 1) * 0.5,
                'worked_minutes' => ($i % 6) * 30,
            ];

            if (in_array($status, [TicketStatus::Testing, TicketStatus::Tested], true)) {
                $attributes['tester_id'] = $developer->id;
            }

            if ($status === TicketStatus::Waiting) {
                $attributes['waiting_reason'] = 'In attesa di riscontro dal socio.';
            }

            if ($status === TicketStatus::Problem) {
                $attributes['problem_reason'] = 'Bloccato da un problema tecnico da chiarire.';
            }

            if (in_array($status, [TicketStatus::Released, TicketStatus::Done], true)) {
                $attributes['released_at'] = $statusChangedAt;
            }

            if ($status === TicketStatus::Done) {
                $attributes['done_at'] = $statusChangedAt;
            }

            $ticket = Ticket::query()->create($attributes);
            $ticket->tags()->attach($tags[$i % count($tags)]->id);

            $this->seedTicketConversation($ticket, $customer, $assignee, $statusChangedAt, $title);

            $tickets[] = $ticket;
        }

        return $tickets;
    }

    private function seedTicketConversation(Ticket $ticket, User $customer, User $assignee, Carbon $statusChangedAt, string $title): void
    {
        TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $customer->id,
            'channel' => TicketMessageChannel::Web,
            'body_text' => "Segnalazione del socio: {$title}.",
            'posted_at' => $statusChangedAt,
        ]);

        TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $assignee->id,
            'channel' => TicketMessageChannel::Web,
            'body_text' => 'Presa in carico dal team di supporto.',
            'posted_at' => $statusChangedAt->copy()->addHour(),
        ]);
    }

    /**
     * @param  array<string, User>  $roleUsers
     */
    private function seedFundraising(array $roleUsers): void
    {
        $fundraisingUser = $roleUsers[UserRole::Fundraising->value];

        $evaluated = FundraisingOpportunity::query()->create([
            'name' => 'Bando Montagna per Tutti 2026',
            'official_url' => 'https://example.org/bandi/montagna-per-tutti-2026',
            'endowment_fund' => 250000,
            'deadline' => now()->addMonths(3)->toDateString(),
            'program_name' => 'Montagna per Tutti',
            'sponsor' => 'Fondazione CAI',
            'cofinancing_quota' => 20,
            'max_contribution' => 30000,
            'territorial_scope' => TerritorialScope::National,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
            'evaluated_by' => $fundraisingUser->id,
            'evaluated_at' => now(),
        ]);

        $positiveTotal = 0;
        $negativeTotal = 0;

        foreach (FundraisingEvaluationCriterion::cases() as $criterion) {
            $score = intdiv($criterion->min() + $criterion->max(), 2);

            FundraisingEvaluationScore::query()->create([
                'fundraising_opportunity_id' => $evaluated->id,
                'criterion_key' => $criterion,
                'score' => $score,
                'notes' => "Valutazione di collaudo UAT per {$criterion->getLabel()}.",
            ]);

            if ($score >= 0) {
                $positiveTotal += $score;
            } else {
                $negativeTotal += $score;
            }
        }

        $evaluated->update([
            'evaluation_positive_total' => $positiveTotal,
            'evaluation_negative_total' => $negativeTotal,
            'evaluation_total' => $positiveTotal + $negativeTotal,
        ]);

        FundraisingOpportunity::query()->create([
            'name' => 'Fondo europeo tutela sentieri alpini',
            'deadline' => now()->addMonths(6)->toDateString(),
            'territorial_scope' => TerritorialScope::European,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
        ]);

        FundraisingOpportunity::query()->create([
            'name' => 'Contributo regionale manutenzione rifugi',
            'deadline' => now()->addMonth()->toDateString(),
            'territorial_scope' => TerritorialScope::Regional,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
        ]);

        FundraisingProject::query()->create([
            'title' => 'Sistemazione sentiero CAI Aosta',
            'fundraising_opportunity_id' => $evaluated->id,
            'lead_user_id' => $fundraisingUser->id,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
            'status' => FundraisingProjectStatus::Submitted,
            'requested_amount' => 15000,
            'submitted_at' => now()->toDateString(),
        ]);

        FundraisingProject::query()->create([
            'title' => 'Portale digitale prenotazione rifugi',
            'fundraising_opportunity_id' => $evaluated->id,
            'lead_user_id' => $fundraisingUser->id,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
            'status' => FundraisingProjectStatus::Draft,
            'requested_amount' => 8000,
        ]);
    }

    private function printCredentials(): void
    {
        $this->console?->newLine();
        $this->console?->info('Credenziali di collaudo UAT (ambiente non-prod):');

        foreach ($this->credentials as $name => $credential) {
            $this->console?->line("- {$name}: {$credential['email']} / {$credential['password']}");
        }
    }
}
