<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Models\ActivityReport;

class ActivityReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::ActivityReportViewAny, Permission::ActivityReportViewOwn]);
    }

    /**
     * `activity-report.view.any` vede qualunque report; `activity-report.view.own`
     * (l'unico permesso dato a customer/manager per un proprio owner, §9.4) vede
     * SOLO i report di cui è effettivamente owner — mai un altro owner anche via
     * id manipolato sull'URL (US-409).
     */
    public function view(User $user, ActivityReport $activityReport): bool
    {
        if ($user->can(Permission::ActivityReportViewAny)) {
            return true;
        }

        return $user->can(Permission::ActivityReportViewOwn) && $activityReport->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ActivityReportCreate);
    }

    public function update(User $user, ActivityReport $activityReport): bool
    {
        return $user->can(Permission::ActivityReportUpdate);
    }

    public function delete(User $user, ActivityReport $activityReport): bool
    {
        return $user->can(Permission::ActivityReportDelete);
    }

    public function generatePdf(User $user, ActivityReport $activityReport): bool
    {
        return $user->can(Permission::ActivityReportGeneratePdf);
    }
}
