<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'locale'])]
class Organization extends Model
{
    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')->withTimestamps();
    }

    /**
     * @return HasMany<ActivityReport, $this>
     */
    public function activityReports(): HasMany
    {
        return $this->hasMany(ActivityReport::class, 'owner_organization_id');
    }
}
