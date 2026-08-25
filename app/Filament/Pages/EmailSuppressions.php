<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailSuppression;
use App\Filament\Widgets\EmailPipelineMetricsOverview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Pagina "Soppressioni" (§7.5.5/§7.7, US-323): elenco `email_suppressions`
 * filtrabile per motivo, con azione di rimozione (riabilita l'invio verso
 * l'indirizzo), più le metriche essenziali del sottosistema email in testa
 * alla pagina ({@see EmailPipelineMetricsOverview}). Sola lettura per chi ha
 * `email.view` (US-321), l'azione di rimozione è visibile solo con
 * `email.manage` — stesso schema di gate a due livelli già usato da
 * `ViewEmailMessage`/`EmailQuarantine` (US-322).
 *
 * Nessun blade dedicato: `content()` compone `EmbeddedTable` sulla view
 * generica del pacchetto, stesso pattern di {@see EmailQuarantine} (US-322).
 */
class EmailSuppressions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Soppressioni';

    protected static ?string $navigationLabel = 'Soppressioni';

    protected static string|UnitEnum|null $navigationGroup = 'Email';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(Permission::EmailView);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmailPipelineMetricsOverview::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(EmailSuppression::query())
            ->columns([
                TextColumn::make('email')->label('Indirizzo')->searchable(),
                TextColumn::make('reason')->label('Motivo')->badge(),
                TextColumn::make('bounce_count')->label('Bounce'),
                TextColumn::make('expires_at')->label('Scade il')->dateTime()->placeholder('Mai'),
                TextColumn::make('created_at')->label('Creata il')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('reason')
                    ->label('Motivo')
                    ->options(collect(SuppressionReason::cases())->mapWithKeys(
                        fn (SuppressionReason $reason): array => [$reason->value => $reason->getLabel()],
                    )),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                $this->removeAction(),
            ]);
    }

    private function removeAction(): Action
    {
        return Action::make('remove')
            ->label('Rimuovi')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (): bool => (bool) Auth::user()?->can(Permission::EmailManage))
            ->requiresConfirmation()
            ->action(function (EmailSuppression $record): void {
                $record->delete();

                Notification::make()->success()->title('Soppressione rimossa, invio riabilitato')->send();
            });
    }
}
