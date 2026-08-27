<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentationPages\Tables;

use App\Domain\Documentation\Enums\DocumentationCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * `body` è `->searchable()` ma `->toggleable(isToggledHiddenByDefault: true)`
 * (non `->hidden()`, US-405 §6.4.2): una colonna nascosta con `->hidden()`
 * viene esclusa dalla definizione della tabella, quindi anche dalla ricerca
 * full-text — qui deve restare interrogabile pur non essendo visibile per
 * default (il corpo HTML non è leggibile in una cella di tabella). Filament
 * combina in OR tutte le colonne `->searchable()` sulla stessa query di
 * ricerca, stesso meccanismo già in uso per `tickets.title`.
 */
class DocumentationPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Titolo')->searchable()->limit(60),
                TextColumn::make('category')->label('Categoria')->badge(),
                TextColumn::make('body')
                    ->label('Contenuto')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(80),
                TextColumn::make('created_at')->label('Creata il')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(collect(DocumentationCategory::cases())->mapWithKeys(
                        fn (DocumentationCategory $category): array => [$category->value => $category->getLabel()],
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
