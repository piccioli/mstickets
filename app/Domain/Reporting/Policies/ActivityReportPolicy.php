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

    public function view(User $user, ActivityReport $activityReport): bool
    {
        return $this->viewAny($user);
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
