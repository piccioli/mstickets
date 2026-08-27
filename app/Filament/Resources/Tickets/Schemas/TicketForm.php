<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Schemas;

use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Rules\TicketParentDepthRule;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use App\Filament\Resources\Tickets\Support\TicketTransitionActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Form condiviso da CreateTicket/EditTicket (US-110). `status` NON è mai un campo
 * modificabile qui (AC #1): è un `Placeholder` di sola lettura (badge), i cambi di
 * stato passano SOLO dalle action di transizione dinamiche
 * ({@see TicketTransitionActions}).
 *
 * I campi "interni" (assegnazione/classificazione/link ambienti/tempo) sono
 * `->hidden()` — non solo `->disabled()` — per chi non ha
 * `ticket.manage-internal-fields` ({@see TicketFieldAccess}): un componente
 * nascosto non viene dehydratato, quindi un cliente non può alterarli nemmeno con
 * una `fillForm()` manipolata (stesso pattern di sicurezza già usato per le
 * sezioni Ruoli/Permessi di `UserForm`, US-021).
 */
class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titolo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->visible(fn (string $operation): bool => $operation !== 'edit' || TicketFieldAccess::canManageInternalFields()),
                        Placeholder::make('title_readonly')
                            ->label('Titolo')
                            ->columnSpanFull()
                            ->content(fn (?Ticket $record): string => (string) $record?->title)
                            ->visible(fn (string $operation): bool => $operation === 'edit' && ! TicketFieldAccess::canManageInternalFields()),
                        Placeholder::make('status')
                            ->label('Stato')
                            ->content(fn (?Ticket $record) => self::statusBadge($record))
                            ->visible(fn (string $operation): bool => $operation !== 'create'),
                        Select::make('parent_id')
                            ->label('Ticket padre')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'title',
                                modifyQueryUsing: fn (Builder $query, ?Ticket $record): Builder => $query
                                    ->whereNull('parent_id')
                                    ->when($record?->exists, fn (Builder $q) => $q->whereKeyNot($record->getKey())),
                            )
                            ->searchable()
                            ->preload()
                            ->rules(fn (?Ticket $record): array => [new TicketParentDepthRule($record)]),
                        Textarea::make('description')
                            ->label('Descrizione interna')
                            ->rows(4)
                            ->columnSpanFull()
                            ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields()),
                    ]),

                Section::make('Assegnazione e classificazione')
                    ->columns(2)
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields())
                    ->schema([
                        Select::make('requester_id')
                            ->label('Richiedente')
                            ->relationship('requester', 'name', modifyQueryUsing: self::activeUsersQuery(...))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('assignee_id')
                            ->label('Assegnatario')
                            ->relationship('assignee', 'name', modifyQueryUsing: self::activeUsersQuery(...))
                            ->searchable()
                            ->preload(),
                        Select::make('tester_id')
                            ->label('Tester')
                            ->relationship('tester', 'name', modifyQueryUsing: self::activeUsersQuery(...))
                            ->searchable()
                            ->preload(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(collect(TicketType::cases())->mapWithKeys(
                                fn (TicketType $type): array => [$type->value => $type->getLabel()],
                            )),
                        Select::make('priority')
                            ->label('Priorità')
                            ->options(collect(TicketPriority::cases())->mapWithKeys(
                                fn (TicketPriority $priority): array => [$priority->value => $priority->getLabel()],
                            )),
                        Select::make('fundraising_project_id')
                            ->label('Progetto fundraising')
                            ->relationship('fundraisingProject', 'title', modifyQueryUsing: self::visibleFundraisingProjectsQuery(...))
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Link ambienti')
                    ->columns(2)
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields())
                    ->schema([
                        TextInput::make('staging_url')->label('URL staging')->url()->maxLength(255),
                        TextInput::make('production_url')->label('URL produzione')->url()->maxLength(255),
                    ]),

                Section::make('Tempo')
                    ->columns(2)
                    ->hidden(fn (): bool => ! TicketFieldAccess::canManageInternalFields())
                    ->schema([
                        TextInput::make('estimated_hours')->label('Ore stimate')->numeric()->step(0.5),
                        Placeholder::make('worked_minutes_display')
                            ->label('Ore lavorate')
                            ->content(fn (?Ticket $record): string => self::formatWorkedMinutes($record === null ? 0 : (int) $record->worked_minutes)),
                    ]),
            ]);
    }

    /**
     * Formato leggibile "Xh Ym" per `worked_minutes` (AC #3): mai i minuti grezzi in
     * UI. Riusato anche da `TicketInfolist`.
     */
    public static function formatWorkedMinutes(int $minutes): string
    {
        return sprintf('%dh %dm', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Esclude gli utenti disattivati dalle select di richiedente/assegnatario/
     * tester (US-020, `User::scopeActive()`): estratto in un metodo tipizzato
     * invece di una closure inline perché il parametro `Builder` di
     * `modifyQueryUsing()` non porta il generico del modello collegato.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private static function activeUsersQuery(Builder $query): Builder
    {
        return $query->active();
    }

    /**
     * Solo i progetti fundraising visibili all'utente corrente secondo
     * `FundraisingProjectPolicy` (US-507, §6.6.3): chi ha `fundraising.view.any`
     * vede tutti i progetti, chi ha solo `fundraising.view.involved` solo quelli
     * coinvolti ({@see FundraisingProject::scopeInvolving()}, US-506), chi non ha
     * nessuno dei due permessi non vede alcun progetto in questa select — mai
     * l'intero elenco indiscriminatamente, anche se il campo vive nella sezione
     * "interna" già riservata a chi gestisce i ticket a fondo.
     *
     * @param  Builder<FundraisingProject>  $query
     * @return Builder<FundraisingProject>
     */
    private static function visibleFundraisingProjectsQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can(Permission::FundraisingViewAny)) {
            return $query;
        }

        if ($user->can(Permission::FundraisingViewInvolved)) {
            return $query->involving($user);
        }

        return $query->whereRaw('1 = 0');
    }

    private static function statusBadge(?Ticket $record): HtmlString
    {
        if (! $record?->exists) {
            return new HtmlString('—');
        }

        $status = $record->status;

        return new HtmlString(Blade::render(
            '<x-filament::badge :color="$color" :icon="$icon">{{ $label }}</x-filament::badge>',
            [
                'color' => $status->getColor(),
                'icon' => $status->getIcon(),
                'label' => $status->getLabel(),
            ],
        ));
    }
}
