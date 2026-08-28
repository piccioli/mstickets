<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Filament\Resources\Users\Support\DeactivateUserAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Actions\Impersonate;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.name')
                    ->label('Ruoli')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn (string $state): string => UserRole::tryFrom($state)?->getColor() ?? 'gray'),
                TextColumn::make('customer_type')
                    ->label('Tipo cliente')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('deactivated_at')
                    ->label('Disattivato dal')
                    ->dateTime()
                    ->placeholder('Attivo')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('customer_type')
                    ->label('Tipo cliente')
                    ->options(collect(CustomerType::cases())->mapWithKeys(
                        fn (CustomerType $type): array => [$type->value => $type->getLabel()],
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // Impersona (§6.7.2, US-607): la visibilità/autorizzazione server-side sono
                // già gestite internamente dal pacchetto tramite User::canImpersonate()/
                // canBeImpersonated() (metodi wired a UserPolicy::impersonate(), §9.4: solo
                // admin) — nessun ->visible() aggiuntivo da duplicare qui. redirectTo esplicito
                // (il default del pacchetto è '/', fuori dal pannello Filament: il banner è
                // registrato solo sull'hook `panels::body.start`, quindi non comparirebbe mai
                // sulla landing pubblica) verso l'home del pannello, che a sua volta smista per
                // ruolo (US-602, `Dashboard::mount()`).
                Impersonate::make()
                    ->redirectTo(fn (): string => Filament::getCurrentOrDefaultPanel()->getUrl()),
                // Disattiva/Riattiva (§6.7.5, US-608): vedi DeactivateUserAction per il dettaglio.
                DeactivateUserAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
