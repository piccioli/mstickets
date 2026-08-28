<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\Log;

/**
 * Stage 7 (§14 del PRD, Fase 7): deduce `customer_type`/`region` (US-701) dal
 * `name` degli utenti già importati con ruolo `customer` (dipende da `users`
 * e `roles_permissions`: entrambi devono già aver popolato riga e ruolo).
 * Opera solo su v2 — nessuna lettura dalla connessione `legacy`, perché
 * `UsersStage` non altera mai `name` (US-R08), quindi il nome v1 è già quello
 * v2 al momento in cui questo stage gira.
 *
 * Ordine dei pattern (il primo che matcha vince, mai un secondo controllo):
 * GR/GP <regione> → GruppoRegionale; OTCO/SO → OrganoTecnicoStrutturaOperativa
 * (regione sempre null, il dato v1 non la porta mai); "<nome> | <regione>" →
 * Sezione (regione null se il testo dopo "|" è vuoto, mai Generico in quel
 * caso); nessun pattern → Generico.
 */
final class CustomerClassificationStage implements ImportStage
{
    /**
     * Varianti di regione note dal dump v1 che non coincidono con la label
     * canonicalizzata dell'enum (§11.4 del PRD): "Alto Adige" da solo, senza
     * il prefisso "Trentino", indica comunque la stessa regione unificata.
     *
     * @var array<string, Region>
     */
    private const REGION_ALIASES = [
        'ALTO ADIGE' => Region::TrentinoAltoAdige,
    ];

    public function name(): string
    {
        return 'customer_classification';
    }

    public function dependencies(): array
    {
        return ['users', 'roles_permissions'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = User::query()->role(UserRole::Customer->value)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $read = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($query->get() as $user) {
            $read++;

            [$customerType, $regionRaw] = $this->classify((string) $user->name);
            $region = $regionRaw !== null ? $this->normalizeRegion($regionRaw, $user) : null;

            if ($context->isDryRun()) {
                continue;
            }

            if ($user->customer_type === $customerType && $user->region === $region) {
                $skipped++;

                continue;
            }

            // Timestamp disabilitato: altrimenti bump di `updated_at` qui farebbe rilevare
            // un diff spurio a `UsersStage` alla corsa successiva (confronta contro
            // l'`updated_at` fisso del v1), rompendo l'idempotenza dell'intera pipeline.
            $user->timestamps = false;
            $user->forceFill([
                'customer_type' => $customerType,
                'region' => $region,
            ])->save();
            $updated++;
        }

        return new StageResult(read: $read, updated: $updated, skipped: $skipped);
    }

    /**
     * @return array{0: CustomerType, 1: ?string}
     */
    private function classify(string $name): array
    {
        $name = trim($name);

        if (preg_match('/^(GR|GP)\s+(.+)$/ui', $name, $matches) === 1) {
            return [CustomerType::GruppoRegionale, trim($matches[2])];
        }

        if (preg_match('#^OTCO\s*/\s*SO\b#ui', $name) === 1) {
            return [CustomerType::OrganoTecnicoStrutturaOperativa, null];
        }

        if (str_contains($name, '|')) {
            $region = trim(substr($name, strpos($name, '|') + 1));

            return [CustomerType::Sezione, $region !== '' ? $region : null];
        }

        return [CustomerType::Generico, null];
    }

    private function normalizeRegion(string $raw, User $user): ?Region
    {
        $canonical = $this->canonicalize($raw);

        foreach (Region::cases() as $region) {
            if ($this->canonicalize($region->label()) === $canonical) {
                return $region;
            }
        }

        foreach (self::REGION_ALIASES as $alias => $region) {
            if ($this->canonicalize($alias) === $canonical) {
                return $region;
            }
        }

        Log::warning(sprintf(
            'CustomerClassificationStage: regione "%s" non normalizzabile per l\'utente #%d (%s), region lasciata null.',
            $raw,
            $user->id,
            $user->email,
        ));

        return null;
    }

    private function canonicalize(string $value): string
    {
        $value = str_replace(['\'', '’', '-'], ' ', $value);
        $value = mb_strtoupper(trim($value));

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
