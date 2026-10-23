<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use App\Domain\CaiDirectory\Models\CaiTrail;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Collection;

/**
 * Ordinamento dell'elenco dei sentieri (Fase 10.6, US-1054), come nel design "Sentieri di Sezione": numero (ordine
 * naturale, "2" prima di "10"), km, stato di accatastamento. Il valore è quello dell'URL (`?ordina=km-desc`); il default
 * (numero crescente) non va nell'URL. Un numero o una lunghezza mancanti vanno sempre in fondo; a parità di km o di stato
 * si ordina per numero.
 */
enum CaiTrailSort: string implements HasLabel
{
    case NumberAsc = 'numero';
    case NumberDesc = 'numero-desc';
    case KmDesc = 'km-desc';
    case KmAsc = 'km';
    case StatusDesc = 'stato-desc';
    case StatusAsc = 'stato';

    /** Etichetta del menu "Ordina" (mobile). */
    public function getLabel(): string
    {
        return match ($this) {
            self::NumberAsc => 'Ordina per numero',
            self::NumberDesc => 'Numero: decrescente',
            self::KmDesc => 'Km: dal più lungo',
            self::KmAsc => 'Km: dal più corto',
            self::StatusDesc => 'Stato: da Validato a Difficoltà',
            self::StatusAsc => 'Stato: da Difficoltà a Validato',
        };
    }

    public function column(): string
    {
        return match ($this) {
            self::NumberAsc, self::NumberDesc => 'numero',
            self::KmAsc, self::KmDesc => 'km',
            self::StatusAsc, self::StatusDesc => 'stato',
        };
    }

    public function isDescending(): bool
    {
        return in_array($this, [self::NumberDesc, self::KmDesc, self::StatusDesc], true);
    }

    /**
     * Clic sull'intestazione di una colonna (`numero`, `km`, `stato`): sulla colonna attiva inverte il verso, su
     * un'altra parte dal numero crescente o dai km / dallo stato decrescenti, come nel design.
     */
    public function toggled(string $column): self
    {
        if ($column === $this->column()) {
            return match ($this) {
                self::NumberAsc => self::NumberDesc,
                self::NumberDesc => self::NumberAsc,
                self::KmDesc => self::KmAsc,
                self::KmAsc => self::KmDesc,
                self::StatusDesc => self::StatusAsc,
                self::StatusAsc => self::StatusDesc,
            };
        }

        return match ($column) {
            'km' => self::KmDesc,
            'stato' => self::StatusDesc,
            default => self::NumberAsc,
        };
    }

    /**
     * @param  Collection<int, CaiTrail>  $trails
     * @return Collection<int, CaiTrail>
     */
    public function sort(Collection $trails): Collection
    {
        $byNumber = static function (CaiTrail $a, CaiTrail $b): int {
            $missing = blank($a->ref) <=> blank($b->ref);

            return $missing ?: strnatcasecmp((string) $a->ref, (string) $b->ref) ?: $a->id <=> $b->id;
        };
        $direction = $this->isDescending() ? -1 : 1;

        return $trails->sort(function (CaiTrail $a, CaiTrail $b) use ($byNumber, $direction): int {
            return match ($this->column()) {
                'numero' => blank($a->ref) || blank($b->ref) ? $byNumber($a, $b) : $direction * $byNumber($a, $b),
                'km' => $a->length_km === null || $b->length_km === null
                    ? (($a->length_km === null) <=> ($b->length_km === null) ?: $byNumber($a, $b))
                    : ($direction * ((float) $a->length_km <=> (float) $b->length_km) ?: $byNumber($a, $b)),
                default => $direction * ($a->status->value <=> $b->status->value) ?: $byNumber($a, $b),
            };
        })->values();
    }
}
