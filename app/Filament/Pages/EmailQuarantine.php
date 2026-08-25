<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\AssignEmailMessageSender;
use App\Domain\Mail\Actions\CreateEmailSenderAndAssign;
use App\Domain\Mail\Actions\ResolveEmailSender;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * Pagina/tab dedicato "Quarantena" (§7.3.8/§7.7, US-322): elenca le sole email
 * inbound in `status = quarantined` (mittente non identificato da
 * {@see ResolveEmailSender}) con le due azioni
 * specifiche di US-308 — "associa a utente esistente"/"crea nuovo utente e
 * ticket" — entrambe seguite dal riprocessamento della pipeline. Stesso
 * gate `email.manage` delle azioni sul dettaglio (US-322, {@see
 * \App\Filament\Resources\EmailMessages\Pages\ViewEmailMessage}), più
 * restrittivo del sola-lettura `email.view` di {@see
 * \App\Filament\Resources\EmailMessages\EmailMessageResource} (US-321).
 *
 * Nessun blade dedicato: `content()` compone `EmbeddedTable` sulla view
 * generica del pacchetto (`filament-panels::pages.page`, stesso pattern di
 * `ListRecords`), evitando un file `.blade.php` che si limiterebbe a
 * incapsulare `{{ $this->table }}`.
 */
class EmailQuarantine extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Quarantena';

    protected static ?string $navigationLabel = 'Quarantena';

    protected static string|UnitEnum|null $navigationGroup = 'Email';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(Permission::EmailManage);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(EmailMessage::query()
                ->where('direction', EmailDirection::Inbound)
                ->where('status', EmailStatus::Quarantined))
            ->columns([
                TextColumn::make('from_email')->label('Mittente'),
                TextColumn::make('from_name')->label('Nome')->placeholder('—'),
                TextColumn::make('subject')->label('Oggetto')->limit(50)->placeholder('—'),
                TextColumn::make('created_at')->label('Ricevuta il')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                $this->assignExistingAction(),
                $this->createAndAssignAction(),
            ]);
    }

    private function assignExistingAction(): Action
    {
        return Action::make('assign_existing')
            ->label('Associa a utente esistente')
            ->icon('heroicon-o-user')
            ->schema([
                Select::make('user_id')
                    ->label('Utente')
                    ->options(fn () => User::query()->active()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, EmailMessage $record): void {
                $actor = self::actor();
                $sender = User::query()->find($data['user_id']);

                if ($actor === null || $sender === null) {
                    return;
                }

                try {
                    AssignEmailMessageSender::run($record, $sender, $actor);
                } catch (RuntimeException $exception) {
                    self::notifyFailure($exception);

                    return;
                }

                self::notifySuccess('Mittente associato e messaggio riprocessato');
            });
    }

    private function createAndAssignAction(): Action
    {
        return Action::make('create_and_assign')
            ->label('Crea nuovo utente e ticket')
            ->icon('heroicon-o-user-plus')
            ->schema([
                TextInput::make('name')->label('Nome')->required(),
                TextInput::make('email')->label('Email')->email()->required(),
            ])
            ->fillForm(fn (EmailMessage $record): array => [
                'name' => $record->from_name ?? '',
                'email' => $record->from_email,
            ])
            ->action(function (array $data, EmailMessage $record): void {
                $actor = self::actor();

                if ($actor === null) {
                    return;
                }

                try {
                    CreateEmailSenderAndAssign::run($record, (string) $data['name'], (string) $data['email'], $actor);
                } catch (RuntimeException $exception) {
                    self::notifyFailure($exception);

                    return;
                }

                self::notifySuccess('Nuovo utente creato e messaggio riprocessato');
            });
    }

    private static function actor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function notifySuccess(string $title): void
    {
        Notification::make()->success()->title($title)->send();
    }

    private static function notifyFailure(RuntimeException $exception): void
    {
        Notification::make()->danger()->title('Azione non riuscita')->body($exception->getMessage())->send();
    }
}
