<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Crea un utente a cui sono concessi esattamente i permessi indicati (creando la riga
 * `permissions` se non esiste ancora). Usata dai test delle policy (US-019) per verificare
 * il deny-by-default senza dover eseguire l'intero RolePermissionSeeder.
 */
function userWithPermissions(PermissionEnum ...$permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
    }

    if ($permissions !== []) {
        $user->givePermissionTo(array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            $permissions,
        ));
    }

    return $user;
}

/**
 * Esegue una Validation Rule (US-102) su un valore isolato e riporta se `$fail()` è
 * stato invocato, senza dover passare da un Validator/richiesta HTTP completa.
 */
function ruleFails(ValidationRule $rule, mixed $value, string $attribute = 'value'): bool
{
    $failed = false;

    $rule->validate($attribute, $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

/**
 * Crea un ticket con i soli attributi obbligatori dello schema (`title`,
 * `status_changed_at`), riusato da qualunque test che ha solo bisogno di un ticket
 * "esiste" senza badare al contenuto degli altri campi (macchina a stati, Action, Rule).
 *
 * @param  array<string, mixed>  $attributes
 */
function ticket(array $attributes = []): Ticket
{
    return Ticket::create(array_merge([
        'title' => 'Errore login',
        'status_changed_at' => now(),
    ], $attributes))->fresh();
}

/**
 * Crea un utente con l'email configurata come utente di sistema (§6.2.1): riconosciuto
 * da `User::isSystem()`/attore `TransitionActor::System` senza dover passare dal comando
 * `orchestrator:doctor` in un test.
 */
function systemUser(): User
{
    return User::factory()->create(['email' => config('orchestrator.system_user.email')]);
}

/**
 * Crea un ticket_message pubblico/web con i soli attributi obbligatori dello schema
 * (`ticket_id`, `channel`, `posted_at`), riusato da qualunque test che ha solo bisogno
 * di un messaggio "esiste" a cui allegare file (US-107) senza passare da
 * `PostTicketMessage::run()` (che sanitizza/emette eventi non pertinenti a quei test).
 *
 * @param  array<string, mixed>  $attributes
 */
function ticketMessage(array $attributes = []): TicketMessage
{
    return TicketMessage::create(array_merge([
        'ticket_id' => ticket()->id,
        'channel' => 'web',
        'posted_at' => now(),
    ], $attributes))->fresh();
}

/**
 * Crea un `ticket_log` con i soli attributi obbligatori dello schema (`ticket_id`,
 * `event`, `occurred_at`), riusato dai test di `WorkedTimeCalculator`/
 * `RecalculateWorkedTime` (US-109) per costruire una sequenza di log senza passare
 * da `ChangeTicketStatus` (che applicherebbe anche i guard della macchina a stati,
 * non pertinenti a quei test).
 *
 * @param  array<string, mixed>  $attributes
 */
function ticketLog(Ticket $ticket, array $attributes = []): TicketLog
{
    return TicketLog::create(array_merge([
        'ticket_id' => $ticket->id,
        'event' => TicketLogEvent::StatusChanged,
        'occurred_at' => now(),
    ], $attributes));
}

/**
 * Assegna un ruolo applicativo (Spatie) a uno User già esistente, creando la riga
 * `roles` se non esiste ancora. Usato dai query object di US-111 che distinguono
 * i ticket in base al ruolo del richiedente (es. `AllCustomerTicketsQuery`,
 * `InternalTicketsQuery`), non dai soli permessi diretti come `userWithPermissions()`.
 */
function withRole(User $user, UserRole $role): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

/**
 * Assegna un ruolo applicativo "vuoto" solo per superare il gate d'accesso al
 * pannello Filament (§9.1, US-020), isolando il test sui soli permessi diretti
 * concessi da `userWithPermissions()`. Spostato qui da `TicketResourceTest.php`
 * (US-110) per essere riusato da qualunque test Filament sul dominio Ticketing
 * (es. `TicketsTableFiltersTest.php`, US-112) senza rischiare il fatal error di
 * redeclare se più file lo dichiarassero localmente.
 */
function grantTicketPanelRole(User $user, UserRole $role = UserRole::Developer): User
{
    Role::query()->firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
    $user->assignRole($role->value);

    return $user->fresh();
}

/**
 * Crea un Tag con i soli attributi obbligatori dello schema (`name`, `slug`
 * univoco), riusato dai test dei filtri di `TicketsTable` (US-112) senza dover
 * costruire uno slug a mano ad ogni chiamata.
 *
 * @param  array<string, mixed>  $attributes
 */
function tag(array $attributes = []): Tag
{
    $name = $attributes['name'] ?? 'Commessa '.Str::random(8);

    return Tag::create(array_merge([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(6),
    ], $attributes))->fresh();
}
