<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Pages;

use App\Domain\CaiDirectory\Actions\UploadCaiDocumentManually;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ViewCaiSection extends ViewRecord
{
    protected static string $resource = CaiSectionResource::class;

    protected function getHeaderActions(): array
    {
        $section = $this->getRecord();

        abort_unless($section instanceof CaiSection, 404);

        return [
            // Sola lettura per il resto (US-804): nessun'altra azione di modifica, l'anagrafica
            // CAI viene aggiornata solo da una nuova esecuzione dell'importer datapack RUNTS-CAI.
            $this->uploadDocumentAction($section),
        ];
    }

    /**
     * Gate dedicato (`Permission::CaiDirectoryUploadDocument`), distinto dal permesso di sola
     * visualizzazione dell'anagrafica: chi può vedere le sezioni non può necessariamente
     * caricare documenti al loro interno.
     */
    private function uploadDocumentAction(CaiSection $section): Action
    {
        $registrations = $section->runtsRegistrations;

        return Action::make('upload_document')
            ->label('Carica documento')
            ->icon('heroicon-o-arrow-up-tray')
            ->visible(fn (): bool => Auth::user()?->can(Permission::CaiDirectoryUploadDocument) ?? false)
            ->schema([
                ...($registrations->isNotEmpty() ? [
                    Select::make('registration_id')
                        ->label('Registrazione RUNTS')
                        ->options($registrations->mapWithKeys(fn (CaiRuntsRegistration $r): array => [$r->id_runts => $r->name ?? $r->id_runts]))
                        ->placeholder('Nessuna (documento generico della sezione)'),
                ] : []),
                TextInput::make('document_type')
                    ->label('Tipo documento')
                    ->required()
                    ->placeholder('es. bilancio_esercizio'),
                TextInput::make('year')
                    ->label('Anno')
                    ->numeric(),
                TextInput::make('title')
                    ->label('Titolo'),
                FileUpload::make('file')
                    ->label('File')
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['application/pdf']),
            ])
            ->action(function (array $data) use ($section): void {
                $user = Auth::user();

                if (! $user instanceof User) {
                    return;
                }

                $registration = ($data['registration_id'] ?? null) !== null
                    ? $section->runtsRegistrations->firstWhere('id_runts', $data['registration_id'])
                    : null;

                try {
                    UploadCaiDocumentManually::run(
                        $user,
                        $section,
                        $registration,
                        (string) $data['document_type'],
                        $data['year'] !== null ? (int) $data['year'] : null,
                        $data['title'] !== null ? (string) $data['title'] : null,
                        $data['file'],
                    );
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Caricamento non riuscito')
                        ->body(collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()->success()->title('Documento caricato')->send();
            });
    }
}
