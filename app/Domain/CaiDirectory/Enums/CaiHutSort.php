<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use App\Domain\CaiDirectory\Models\CaiHut;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Ordinamento dell'elenco dei rifugi (Fase 10.4, US-1034), come nel design "Rifugi di Sezione": nome A–Z / Z–A e quota
 * crescente / decrescente. Il valore è quello dell'URL (`?ordina=quota-desc`); il default (nome A–Z) non va nell'URL.
 * Il nome si confronta senza accenti né maiuscole e in ordine naturale ("Bivacco 2" prima di "Bivacco 10"); una quota
 * mancante va sempre in fondo, a parità di quota si ordina per nome.
 */
enum CaiHutSort: string implements HasLabel
{
    case NameAsc = 'nome';
    case NameDesc = 'nome-desc';
    case AltitudeDesc = 'quota-desc';
    case AltitudeAsc = 'quota';

    /** Etichetta del menu "Ordina" (mobile). */
    public function getLabel(): string
    {
        return match ($this) {
            self::NameAsc => 'Nome A–Z',
            self::NameDesc => 'Nome Z–A',
            self::AltitudeDesc => 'Quota più alta',
            self::AltitudeAsc => 'Quota più bassa',
        };
    }

    public function isByName(): bool
    {
        return $this === self::NameAsc || $this === self::NameDesc;
    }

    public function isDescending(): bool
    {
        return $this === self::NameDesc || $this === self::AltitudeDesc;
    }

    /** Clic sull'intestazione "Nome": A–Z, poi Z–A. */
    public function toggledByName(): self
    {
        return $this === self::NameAsc ? self::NameDesc : self::NameAsc;
    }

    /** Clic sull'intestazione "Quota": prima la più alta, poi la più bassa. */
    public function toggledByAltitude(): self
    {
        return $this === self::AltitudeDesc ? self::AltitudeAsc : self::AltitudeDesc;
    }

    /**
     * @param  Collection<int, CaiHut>  $huts
     * @return Collection<int, CaiHut>
     */
    public function sort(Collection $huts): Collection
    {
        $byName = fn (CaiHut $a, CaiHut $b): int => strnatcmp(self::nameKey($a), self::nameKey($b));

        return $huts->sort(function (CaiHut $a, CaiHut $b) use ($byName): int {
            if ($this->isByName()) {
                return $this->isDescending() ? $byName($b, $a) : $byName($a, $b);
            }

            if ($a->altitude === null || $b->altitude === null) {
                return ($a->altitude === null) <=> ($b->altitude === null) ?: $byName($a, $b);
            }

            $altitude = $this->isDescending() ? $b->altitude <=> $a->altitude : $a->altitude <=> $b->altitude;

            return $altitude ?: $byName($a, $b);
        })->values();
    }

    private static function nameKey(CaiHut $hut): string
    {
        return Str::lower(Str::ascii($hut->name));
    }
}
