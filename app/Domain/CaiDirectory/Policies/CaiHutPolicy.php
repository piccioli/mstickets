<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * Rifugi di sola lettura (US-1031): nessuna scrittura da interfaccia, la fonte è rifugi.cai.it. Un rifugio (e i suoi
 * dati di gestione, riservati) lo vedono lo staff con `CaiDirectoryView`, il customer collegato alla Sezione
 * proprietaria (`branch_code`) e il customer GR il cui prefisso (regola della 10.2) contiene il `branch_code`.
 */
class CaiHutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CaiDirectoryView);
    }

    public function view(User $user, CaiHut $caiHut): bool
    {
        if ($user->can(Permission::CaiDirectoryView)) {
            return true;
        }

        $branchCode = (string) $caiHut->branch_code;

        if ($branchCode === '') {
            return false;
        }

        if (CaiSection::query()->whereKey($branchCode)->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($user->customer_type !== CustomerType::GruppoRegionale || $user->region === null) {
            return false;
        }

        $prefix = CaiRegionalGroupDashboardMetrics::prefixOf($user->region);

        return $prefix !== null && str_starts_with($branchCode, $prefix);
    }

    /** Gestore, contratto, canone, proprietà, certificazioni e CIN: stessi profili di {@see view()}. */
    public function viewManagement(User $user, CaiHut $caiHut): bool
    {
        return $this->view($user, $caiHut);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CaiHut $caiHut): bool
    {
        return false;
    }

    public function delete(User $user, CaiHut $caiHut): bool
    {
        return false;
    }
}
