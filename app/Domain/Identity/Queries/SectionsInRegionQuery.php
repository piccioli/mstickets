<?php

declare(strict_types=1);

namespace App\Domain\Identity\Queries;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Sezioni del gruppo regionale" (§8.5 style, US-705): le Sezioni della stessa regione di un
 * Gruppo Regionale. Concetto distinto da `organizations`/`organization_user` (Fase 4, possesso
 * degli Activity Report) — mai unito o confuso con quello.
 */
final class SectionsInRegionQuery
{
    /**
     * @return Builder<User>
     */
    public static function for(Region $region): Builder
    {
        return User::query()
            ->where('customer_type', CustomerType::Sezione)
            ->where('region', $region)
            ->orderBy('name');
    }
}
