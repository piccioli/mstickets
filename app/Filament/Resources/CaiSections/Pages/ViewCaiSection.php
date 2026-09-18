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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                Select::make('year')
                    ->label('Anno')
                    ->options(collect(range(now()->year, now()->year - 20))->mapWithKeys(
                        fn (int $year): array => [$year => (string) $year],
                    ))
                    ->placeholder('—'),
                TextInput::make('title')
                    ->label('Titolo')
                    ->required(),
                FileUpload::make('file')
                    ->label('File')
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['application/pdf'])
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                        if (blank($get('title')) && $state instanceof TemporaryUploadedFile) {
                            $set('title', $state->getClientOriginalName());
                        }
                    }),
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
                    (string) $data['title'],
                    $data['file'],
                );

                Notification::make()->success()->title('Documento caricato')->send();

                // Ricarica l'intera pagina (non solo un refresh Livewire): l'Infolist legge
                // `$section->documents`/`runtsRegistrations` già risolte in memoria all'apertura
                // della pagina, un `CaiDocument` appena creato non ci comparirebbe senza una
                // richiesta nuova. Riporta esplicitamente sul tab "Allegati"
                // (`CaiSectionInfolist::configure()`, `->persistTabInQueryString('tab')` +
                // `Tab::make('Allegati')->id('allegati')`), a prescindere da quale tab fosse
                // attivo prima del caricamento.
                $this->redirect(CaiSectionResource::getUrl('view', ['record' => $section, 'tab' => 'allegati']));
            });
    }
}
