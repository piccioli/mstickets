<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Pages;

use App\Domain\CaiDirectory\Actions\UploadCaiDocumentManually;
use App\Domain\CaiDirectory\Enums\CaiDocumentType;
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
     * caricare documenti al loro interno. Nessun selettore di registrazione RUNTS: quelle nascono
     * solo dalla sync live, mai da questo form — il documento si collega sempre alla sezione.
     */
    private function uploadDocumentAction(CaiSection $section): Action
    {
        return Action::make('upload_document')
            ->label('Carica documento')
            ->icon('heroicon-o-arrow-up-tray')
            ->visible(fn (): bool => Auth::user()?->can(Permission::CaiDirectoryUploadDocument) ?? false)
            ->schema([
                Select::make('document_type')
                    ->label('Tipo documento')
                    ->options(collect(CaiDocumentType::cases())->mapWithKeys(
                        fn (CaiDocumentType $type): array => [$type->value => $type->getLabel()],
                    ))
                    ->required(),
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

                UploadCaiDocumentManually::run(
                    $user,
                    $section,
                    CaiDocumentType::from($data['document_type']),
                    $data['year'] !== null ? (int) $data['year'] : null,
                    $data['title'] !== null ? (string) $data['title'] : null,
                    $data['file'],
                );

                Notification::make()->success()->title('Documento caricato')->send();
            });
    }
}
