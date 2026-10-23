<?php

declare(strict_types=1);

namespace App\Filament\Pages;

/**
 * Tappe SICAI della Sezione: la 10.8 non si fa (in osm2cai quasi nessuna tappa ha una Sezione), quindi la pagina dice che
 * si attendono i dati, come il badge "In attesa" della dashboard.
 */
class SectionSicaiStages extends SectionComingSoonPage
{
    protected static ?string $title = 'Tappe SICAI';

    protected static ?string $slug = 'sezione/tappe-sicai';

    public function message(): string
    {
        return 'In attesa dei dati sull\'assegnazione delle tappe alle Sezioni.';
    }
}
