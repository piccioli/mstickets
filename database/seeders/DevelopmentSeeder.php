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
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Support\Doctor\Checks\SystemUserCheck;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Popola un ambiente di sviluppo realistico su tutti i moduli in scope (§4.2, US-023): il
 * pannello è così navigabile senza dover ripristinare il dump v1. Non eseguibile in produzione.
 */
class DevelopmentSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * @var array<string, array{email: string, password: string}>
     */
    private array $credentials = [];

    /**
     * Copia nullable del comando console: la proprietà ereditata `Seeder::$command` è
     * documentata come sempre presente, ma non lo è quando il seeder gira fuori da
     * `php artisan db:seed` (es. istanziato direttamente nei test).
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
            throw new RuntimeException('DevelopmentSeeder non può essere eseguito in produzione.');
        }

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
            $this->console?->info('DevelopmentSeeder: ticket/tag/documentazione/report/fundraising già presenti, salto la generazione.');
        }

        $this->printCredentials();
    }

    /**
     * @return array<string, User>
     */
    private function seedRoleUsers(): array
    {
        $names = [
            UserRole::Admin->value => 'Anna Amministratore',
            UserRole::Developer->value => 'Dario Sviluppatore',
            UserRole::Manager->value => 'Marco Manager',
            UserRole::Customer->value => 'Cliente CAI',
            UserRole::Fundraising->value => 'Fabia Fundraising',
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
        $names = ['CAI Sezione di Torino', 'CAI Sezione di Bergamo'];

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
            ['title' => 'Come aprire una richiesta di supporto', 'category' => DocumentationCategory::Customer],
            ['title' => 'Guida al pannello ticket per i soci', 'category' => DocumentationCategory::Customer],
            ['title' => 'FAQ abbonamenti e fatturazione', 'category' => DocumentationCategory::Customer],
            ['title' => 'Policy interna di assegnazione ticket', 'category' => DocumentationCategory::Internal],
            ['title' => 'Procedura interna di rilascio in produzione', 'category' => DocumentationCategory::Internal],
        ];

        return array_map(static function (array $page): DocumentationPage {
            $slug = Str::slug($page['title']);

            return DocumentationPage::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'body' => "Contenuto di sviluppo per «{$page['title']}».",
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

        $tickets = [];

        for ($i = 0; $i < 40; $i++) {
            $status = $statuses[$i % count($statuses)];
            $type = $types[$i % count($types)];
            $priority = $priorities[$i % count($priorities)];
            $assignee = $i % 2 === 0 ? $developer : $manager;
            $statusChangedAt = now()->subDays(40 - $i);

            $attributes = [
                'title' => "[{$type->getLabel()}] Ticket di sviluppo #{$i}",
                'description' => "Descrizione generata per l'ambiente di sviluppo (ticket #{$i}).",
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
                $attributes['waiting_reason'] = 'In attesa di risposta dal cliente.';
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

            $this->seedTicketConversation($ticket, $customer, $assignee, $statusChangedAt, $i);

            $tickets[] = $ticket;
        }

        return $tickets;
    }

    private function seedTicketConversation(Ticket $ticket, User $customer, User $assignee, Carbon $statusChangedAt, int $index): void
    {
        $firstMessage = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $customer->id,
            'channel' => TicketMessageChannel::Web,
            'body_text' => "Richiesta iniziale del cliente per il ticket #{$index}.",
            'posted_at' => $statusChangedAt,
        ]);

        TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $assignee->id,
            'channel' => TicketMessageChannel::Web,
            'body_text' => 'Presa in carico dal team di sviluppo.',
            'posted_at' => $statusChangedAt->copy()->addHour(),
        ]);

        if ($index % 4 === 0) {
            $firstMessage
                ->addMediaFromString("Contenuto finto dell'allegato del ticket #{$index}.")
                ->usingFileName("allegato-{$index}.txt")
                ->toMediaCollection('attachments');
        }
    }

    /**
     * @param  array<string, User>  $roleUsers
     * @param  list<Organization>  $organizations
     * @param  list<Ticket>  $tickets
     */
    private function seedActivityReports(array $roleUsers, array $organizations, array $tickets): void
    {
        $customer = $roleUsers[UserRole::Customer->value];

        $userReport = ActivityReport::query()->firstOrCreate(
            ['owner_kind' => ActivityReportOwnerKind::User, 'owner_user_id' => $customer->id, 'period_type' => ActivityReportPeriodType::Monthly, 'year' => 2026, 'month' => 6],
            ['locale' => 'it'],
        );
        $userReport->tickets()->syncWithoutDetaching(array_map(static fn (Ticket $ticket): int => $ticket->id, array_slice($tickets, 0, 5)));

        $organizationReport = ActivityReport::query()->firstOrCreate(
            ['owner_kind' => ActivityReportOwnerKind::Organization, 'owner_organization_id' => $organizations[0]->id, 'period_type' => ActivityReportPeriodType::Annual, 'year' => 2025],
            ['locale' => 'it'],
        );
        $organizationReport->tickets()->syncWithoutDetaching(array_map(static fn (Ticket $ticket): int => $ticket->id, array_slice($tickets, 5, 5)));
    }

    /**
     * @param  array<string, User>  $roleUsers
     */
    private function seedFundraising(array $roleUsers): void
    {
        $fundraisingUser = $roleUsers[UserRole::Fundraising->value];

        $evaluated = FundraisingOpportunity::query()->create([
            'name' => 'Bando Sostieni la Montagna 2026',
            'official_url' => 'https://example.org/bandi/sostieni-la-montagna-2026',
            'endowment_fund' => 250000,
            'deadline' => now()->addMonths(3)->toDateString(),
            'program_name' => 'Sostieni la Montagna',
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
                'notes' => "Valutazione di sviluppo per {$criterion->getLabel()}.",
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
            'name' => 'Fondo europeo cooperazione alpina',
            'deadline' => now()->addMonths(6)->toDateString(),
            'territorial_scope' => TerritorialScope::European,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
        ]);

        FundraisingOpportunity::query()->create([
            'name' => 'Contributo regionale attività CAI',
            'deadline' => now()->addMonth()->toDateString(),
            'territorial_scope' => TerritorialScope::Regional,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
        ]);

        FundraisingProject::query()->create([
            'title' => 'Recupero sentiero alpino',
            'fundraising_opportunity_id' => $evaluated->id,
            'lead_user_id' => $fundraisingUser->id,
            'created_by' => $fundraisingUser->id,
            'responsible_user_id' => $fundraisingUser->id,
            'status' => FundraisingProjectStatus::Submitted,
            'requested_amount' => 15000,
            'submitted_at' => now()->toDateString(),
        ]);

        FundraisingProject::query()->create([
            'title' => 'Rifugio digitale accessibile',
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
        $this->console?->info('Credenziali di sviluppo (ambiente non-prod):');

        foreach ($this->credentials as $name => $credential) {
            $this->console?->line("- {$name}: {$credential['email']} / {$credential['password']}");
        }
    }
}
