<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailMessages\Pages;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\AssignEmailMessageSender;
use App\Domain\Mail\Actions\DiscardEmailMessage;
use App\Domain\Mail\Actions\LinkInboundEmailToTicket;
use App\Domain\Mail\Actions\ReprocessInboundEmailMessage;
use App\Domain\Mail\Actions\RetryOutboundEmailMessage;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\EmailMessages\EmailMessageResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Azioni amministrative sul singolo messaggio (§7.3.8/§7.7, US-322): tutte
 * delegano a un'Action di dominio dedicata (mai una scrittura diretta da qui)
 * e sono visibili solo a chi ha `email.manage` — un utente con il solo
 * `email.view` (US-321) vede la pagina ma nessuno di questi bottoni.
 */
class ViewEmailMessage extends ViewRecord
{
    protected static string $resource = EmailMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->reprocessAction(),
            $this->assignSenderAction(),
            $this->linkToTicketAction(),
            $this->discardAction(),
            $this->resendAction(),
        ];
    }

    private function reprocessAction(): Action
    {
        return Action::make('reprocess')
            ->label('Riprocessa')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (EmailMessage $record): bool => self::canManage()
                && $record->direction === EmailDirection::Inbound
                && in_array($record->status, [
                    EmailStatus::Classified,
                    EmailStatus::Quarantined,
                    EmailStatus::Applied,
                    EmailStatus::Discarded,
                    EmailStatus::Failed,
                ], true))
            ->requiresConfirmation()
            ->action(function (EmailMessage $record): void {
                $actor = self::actor();

                if ($actor === null) {
                    return;
                }

                try {
                    ReprocessInboundEmailMessage::run($record, $actor);
                } catch (RuntimeException $exception) {
                    self::notifyFailure($exception);

                    return;
                }

                self::notifySuccess('Messaggio riprocessato');
            });
    }

    private function assignSenderAction(): Action
    {
        return Action::make('assign_sender')
            ->label('Assegna a utente')
            ->icon('heroicon-o-user')
            ->visible(fn (EmailMessage $record): bool => self::canManage()
                && $record->direction === EmailDirection::Inbound
                && $record->status === EmailStatus::Quarantined)
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

    private function linkToTicketAction(): Action
    {
        return Action::make('link_to_ticket')
            ->label('Collega a ticket')
            ->icon('heroicon-o-link')
            ->visible(fn (EmailMessage $record): bool => self::canManage()
                && $record->direction === EmailDirection::Inbound
                && $record->user_id !== null)
            ->schema([
                Select::make('ticket_id')
                    ->label('Ticket')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Ticket::query()
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('id', $search)
                        ->limit(20)
                        ->pluck('title', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Ticket::query()->find($value)?->title)
                    ->required(),
            ])
            ->action(function (array $data, EmailMessage $record): void {
                $actor = self::actor();
                $ticket = Ticket::query()->find($data['ticket_id']);

                if ($actor === null || $ticket === null) {
                    return;
                }

                try {
                    LinkInboundEmailToTicket::run($record, $ticket, $actor);
                } catch (RuntimeException $exception) {
                    self::notifyFailure($exception);

                    return;
                }

                self::notifySuccess('Messaggio collegato al ticket');
            });
    }

    private function discardAction(): Action
    {
        return Action::make('discard')
            ->label('Scarta')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (EmailMessage $record): bool => self::canManage()
                && $record->direction === EmailDirection::Inbound
                && $record->status !== EmailStatus::Discarded
                && $record->ticket_id === null)
            ->schema([
                Textarea::make('reason')->label('Motivo')->required(),
            ])
            ->action(function (array $data, EmailMessage $record): void {
                $actor = self::actor();

                if ($actor === null) {
                    return;
                }

                try {
                    DiscardEmailMessage::run($record, (string) $data['reason'], $actor);
                } catch (RuntimeException $exception) {
                    self::notifyFailure($exception);

                    return;
                }

                self::notifySuccess('Messaggio scartato');
            });
    }

    private function resendAction(): Action
    {
        return Action::make('resend')
            ->label('Reinvia')
            ->icon('heroicon-o-paper-airplane')
            ->visible(fn (EmailMessage $record): bool => self::canManage()
                && $record->direction === EmailDirection::Outbound
                && in_array($record->status, [EmailStatus::Failed, EmailStatus::Bounced], true))
            ->requiresConfirmation()
            ->action(function (EmailMessage $record): void {
                $actor = self::actor();

                if ($actor === null) {
                    return;
                }

                try {
                    $result = RetryOutboundEmailMessage::run($record, $actor);
                } catch (RuntimeException $exception) {
                    self::notifyFailure($exception);

                    return;
                }

                if ($result->status === EmailStatus::Queued) {
                    self::notifySuccess('Messaggio riaccodato per l\'invio');

                    return;
                }

                $lastNote = $result->logs()->latest('occurred_at')->first()?->notes;

                Notification::make()
                    ->warning()
                    ->title('Reinvio non eseguito')
                    ->body($lastNote ?? 'Il messaggio non può essere reinviato allo stato attuale.')
                    ->send();
            });
    }

    private static function canManage(): bool
    {
        return (bool) Auth::user()?->can(Permission::EmailManage);
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
