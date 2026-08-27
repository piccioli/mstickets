<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\RelationManagers;

use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Raggiungibile solo dalla pagina Edit (nessuna pagina "view" separata su
 * `FundraisingProjectResource`), quindi solo da chi ha `fundraising.update`
 * (§6.6.3, US-507) — stesso idioma di
 * {@see UsersRelationManager}
 * (US-407).
 */
class PartnersRelationManager extends RelationManager
{
    protected static string $relationship = 'partners';

    protected static ?string $title = 'Partner';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['name', 'email']),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
