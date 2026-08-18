<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailMessages\Pages;

use App\Filament\Resources\EmailMessages\EmailMessageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailMessage extends ViewRecord
{
    protected static string $resource = EmailMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sola lettura (US-321): riprocessa/assegna/collega/scarta/reinvia sono
            // azioni di US-322, non ancora costruite qui.
        ];
    }
}
