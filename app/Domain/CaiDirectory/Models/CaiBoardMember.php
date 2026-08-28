<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cai_runts_registration_id', 'role', 'full_name', 'tax_code', 'valid_from', 'valid_to'])]
class CaiBoardMember extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Formato esplicito `Y-m-d`: senza, Eloquent serializza comunque in scrittura
            // col formato generico `Y-m-d H:i:s` (il cast `date` tronca solo in lettura) —
            // su SQLite (nessuna tipizzazione reale delle colonne) questo lasciava un
            // suffisso orario che rompeva il match per idempotenza in
            // CaiDatapackImporter::importBoardMembers() (confrontato con la stringa nuda
            // `Y-m-d` di CaiRuntsDateParser). Su Postgres il problema restava mascherato:
            // la colonna è realmente `date`, quindi il DB stesso troncava l'orario in
            // scrittura — ma l'idempotenza va garantita a prescindere dal driver.
            'valid_from' => 'date:Y-m-d',
            'valid_to' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<CaiRuntsRegistration, $this>
     */
    public function runtsRegistration(): BelongsTo
    {
        return $this->belongsTo(CaiRuntsRegistration::class, 'cai_runts_registration_id', 'id_runts');
    }
}
