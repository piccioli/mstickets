<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Support\DeactivateUserAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use STS\FilamentImpersonate\Actions\Impersonate;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            // Impersona (§6.7.2, US-607): a differenza dell'azione di riga nella tabella,
            // un'azione di pagina richiede esplicitamente ->record() (nessun contesto di riga
            // da cui il pacchetto possa dedurre il bersaglio). Stessa autorizzazione della
            // riga: gestita internamente dal pacchetto via User::canImpersonate()/
            // canBeImpersonated(). redirectTo esplicito: vedi commento in UsersTable.
            Impersonate::make()
                ->record($this->getRecord())
                ->redirectTo(fn (): string => Filament::getCurrentOrDefaultPanel()->getUrl()),
            // Disattiva/Riattiva (§6.7.5, US-608): vedi DeactivateUserAction per il dettaglio.
            DeactivateUserAction::make()
                ->record($this->getRecord()),
        ];
    }
}
