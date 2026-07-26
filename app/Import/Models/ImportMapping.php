<?php

declare(strict_types=1);

namespace App\Import\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source_table', 'source_key', 'target_table', 'target_id'])]
class ImportMapping extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
        ];
    }
}
