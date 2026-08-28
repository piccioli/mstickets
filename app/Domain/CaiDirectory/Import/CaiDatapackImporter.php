<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;

/**
 * Importa/upserta il datapack RUNTS-CAI (`cai:import-datapack`, US-802) nelle tabelle
 * del dominio `App\Domain\CaiDirectory` (create in US-801). Legge il file SQLite tramite
 * una connessione DB dinamica dedicata, aperta in sola lettura via
 * `PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY` (opzione PDO nativa del
 * driver sqlite da PHP 8.1, verificata utilizzabile in questo container: un tentativo di
 * scrittura su quella connessione fallisce con "attempt to write a readonly database" —
 * questo import non ci scrive comunque mai, ma la connessione lo garantisce anche in caso
 * di bug futuro).
 *
 * Ordine di import fisso, dipendenze a cascata: sezioni → sottosezioni (FK sezione) →
 * registrazioni RUNTS (FK sezione, via match codice fiscale) → bilanci/cariche
 * sociali/allegati (FK registrazione). Una `enti` senza match su nessuna `sezioni_cai`
 * non viene importata (§AC US-802): l'insieme degli `id_runts` con match è calcolato
 * UNA VOLTA dai dati sorgente (indipendente da `--dry-run` e dall'esito
 * created/updated/skipped della singola riga) e riusato per scartare coerentemente le
 * righe figlie orfane, altrimenti la FK `cai_financial_statements_registration_fk` (e
 * equivalenti) fallirebbe in scrittura.
 */
final class CaiDatapackImporter
{
    use DiffsAttributes;

    private const CONNECTION_NAME = 'cai_datapack';

    private const DOCUMENTS_DISK = 'cai-documents';

    /**
     * @return array<string, CaiImportTableResult>
     */
    public function import(string $absolutePath, bool $dryRun): array
    {
        $this->registerConnection($absolutePath);

        try {
            $connection = DB::connection(self::CONNECTION_NAME);
            $datapackDir = dirname($absolutePath);

            $sezioniRows = $connection->table('sezioni_cai')->orderBy('codice_cai')->get();
            $sottosezioniRows = $connection->table('sottosezioni_cai')->orderBy('cai_codice')->get();
            $entiRows = $connection->table('enti')->orderBy('id_runts')->get();
            $bilanciRows = $connection->table('bilanci')->orderBy('id')->get();
            $carichiSocialiRows = $connection->table('cariche_sociali')->orderBy('id')->get();
            $allegatiRows = $connection->table('allegati')->orderBy('id')->get();

            $usersByLowerEmail = $this->buildUserIdsByLowerEmail();
            $sectionCodeByLowerTaxCode = $this->buildSectionCodeByLowerTaxCode($sezioniRows);

            $results = [];
            $results['cai_sections'] = $this->importSections($sezioniRows, $usersByLowerEmail, $dryRun);
            $results['cai_subsections'] = $this->importSubsections($sottosezioniRows, $usersByLowerEmail, $dryRun);

            [$registrationsResult, $matchedIdRunts] = $this->importRegistrations($entiRows, $sectionCodeByLowerTaxCode, $dryRun);
            $results['cai_runts_registrations'] = $registrationsResult;

            $results['cai_financial_statements'] = $this->importFinancialStatements($bilanciRows, $matchedIdRunts, $dryRun);
            $results['cai_board_members'] = $this->importBoardMembers($carichiSocialiRows, $matchedIdRunts, $dryRun);
            $results['cai_documents'] = $this->importDocuments($allegatiRows, $matchedIdRunts, $datapackDir, $dryRun);

            return $results;
        } finally {
            DB::purge(self::CONNECTION_NAME);
        }
    }

    private function registerConnection(string $absolutePath): void
    {
        DB::purge(self::CONNECTION_NAME);

        config(['database.connections.'.self::CONNECTION_NAME => [
            'driver' => 'sqlite',
            'database' => $absolutePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
            'options' => [
                PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY,
            ],
        ]]);
    }

    /**
     * @return array<string, int> id utente per email in minuscolo/trim
     */
    private function buildUserIdsByLowerEmail(): array
    {
        return User::query()
            ->whereNotNull('email')
            ->get(['id', 'email'])
            ->mapWithKeys(fn (User $user): array => [Str::lower(trim((string) $user->email)) => $user->id])
            ->all();
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function matchUserId(?string $email, array $usersByLowerEmail): ?int
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return $usersByLowerEmail[Str::lower(trim($email))] ?? null;
    }

    /**
     * @param  Collection<int, \stdClass>  $sezioniRows
     * @return array<string, string> codice_cai per codice fiscale in minuscolo/trim
     */
    private function buildSectionCodeByLowerTaxCode(Collection $sezioniRows): array
    {
        $map = [];

        foreach ($sezioniRows as $row) {
            $taxCode = $row->cai_codice_fiscale !== null ? trim((string) $row->cai_codice_fiscale) : '';

            if ($taxCode === '') {
                continue;
            }

            $map[Str::lower($taxCode)] = (string) $row->codice_cai;
        }

        return $map;
    }

    private function toInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * `cai_sections.latitude`/`longitude` (US-801) sono `decimal(10,7)`: al massimo 3 cifre
     * intere, quindi qualunque valore |x| >= 1000 farebbe fallire l'insert con "numeric field
     * overflow". Sul dataset reale una riga su 529 ha coordinate palesemente corrotte alla
     * fonte (`cai_lat = 25614`, non una latitudine): scartarla a `null`, non far fallire
     * l'intero import per un valore non plausibile di una singola sezione.
     */
    private function toCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $float = (float) $value;

        return abs($float) < 1000.0 ? $float : null;
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function importSections(Collection $rows, array $usersByLowerEmail, bool $dryRun): CaiImportTableResult
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            if ($dryRun) {
                continue;
            }

            $attributes = [
                'name' => $row->cai_denominazione,
                'tax_code' => $row->cai_codice_fiscale,
                'vat_number' => $row->cai_partita_iva,
                'email' => $row->cai_email,
                'pec' => $row->cai_pec,
                'phone_office' => $row->cai_telefono_sede,
                'phone' => $row->cai_telefono,
                'fax' => $row->cai_fax,
                'address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_sede),
                'postal_address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_postale),
                'website' => $row->cai_sito_web,
                'office_hours' => $row->cai_orari,
                'notices' => $row->cai_avvisi,
                'founded_year' => $this->toInt($row->cai_anno_fondazione),
                'members_count' => $this->toInt($row->cai_soci_ultimo_anno),
                'latitude' => $this->toCoordinate($row->cai_lat),
                'longitude' => $this->toCoordinate($row->cai_lon),
                'region' => $row->cai_regione,
                'user_id' => $this->matchUserId($row->cai_email, $usersByLowerEmail),
            ];

            $existing = CaiSection::find((string) $row->codice_cai);

            if ($existing === null) {
                CaiSection::create(['codice_cai' => $row->codice_cai, ...$attributes]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function importSubsections(Collection $rows, array $usersByLowerEmail, bool $dryRun): CaiImportTableResult
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            if ($dryRun) {
                continue;
            }

            $attributes = [
                'cai_section_id' => $row->cai_sezione_codice,
                'name' => $row->cai_nome,
                'email' => $row->cai_email,
                'phone_office' => $row->cai_telefono_sede,
                'phone' => $row->cai_telefono,
                'address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_sede),
                'website' => $row->cai_sito_web,
                'office_hours' => $row->cai_orari,
                'notices' => $row->cai_avvisi,
                'founded_year' => $this->toInt($row->cai_anno_fondazione),
                'members_count' => $this->toInt($row->cai_soci),
                'latitude' => $this->toCoordinate($row->cai_lat),
                'longitude' => $this->toCoordinate($row->cai_lon),
                'user_id' => $this->matchUserId($row->cai_email, $usersByLowerEmail),
            ];

            $existing = CaiSubsection::find((string) $row->cai_codice);

            if ($existing === null) {
                CaiSubsection::create(['cai_codice' => $row->cai_codice, ...$attributes]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<string, string>  $sectionCodeByLowerTaxCode
     * @return array{0: CaiImportTableResult, 1: array<string, true>}
     */
    private function importRegistrations(Collection $rows, array $sectionCodeByLowerTaxCode, bool $dryRun): array
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $matchedIdRunts = [];

        foreach ($rows as $row) {
            $read++;

            $taxCode = $row->codice_fiscale !== null ? trim((string) $row->codice_fiscale) : '';
            $sectionCode = $taxCode !== '' ? ($sectionCodeByLowerTaxCode[Str::lower($taxCode)] ?? null) : null;

            if ($sectionCode === null) {
                $skipped++;

                continue;
            }

            $matchedIdRunts[(string) $row->id_runts] = true;

            if ($dryRun) {
                continue;
            }

            $attributes = [
                'cai_section_id' => $sectionCode,
                'tax_code' => $row->codice_fiscale,
                'name' => $row->denominazione,
                'legal_form' => $row->forma_giuridica,
                'legal_nature' => $row->natura_giuridica,
                'address' => $row->sede_indirizzo,
                'street_number' => $row->sede_civico,
                'municipality' => $row->sede_comune,
                'province' => $row->sede_provincia,
                'region' => $row->sede_regione,
                'postal_code' => $row->sede_cap,
                'latitude' => $row->lat,
                'longitude' => $row->lon,
                'registration_date' => CaiRuntsDateParser::parse($row->data_iscrizione),
                'register_section' => $row->sezione_registro,
                'activity_sectors' => $row->settori_attivita,
                'legal_representative' => $row->rappresentante_legale,
                'website' => $row->sito_web,
                'pec' => $row->pec,
                'official_page_url' => $row->url_dettaglio,
            ];

            $existing = CaiRuntsRegistration::find((string) $row->id_runts);

            if ($existing === null) {
                CaiRuntsRegistration::create(['id_runts' => $row->id_runts, ...$attributes]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return [new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped), $matchedIdRunts];
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<string, true>  $matchedIdRunts
     */
    private function importFinancialStatements(Collection $rows, array $matchedIdRunts, bool $dryRun): CaiImportTableResult
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            if (! isset($matchedIdRunts[(string) $row->id_runts])) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                continue;
            }

            $attributes = [
                'cai_runts_registration_id' => $row->id_runts,
                'year' => $this->toInt($row->anno),
                'general_interest_expenses' => $row->oneri_a_interesse_generale,
                'other_activities_expenses' => $row->oneri_b_attivita_diverse,
                'fundraising_expenses' => $row->oneri_c_raccolta_fondi,
                'financial_expenses' => $row->oneri_d_finanziarie_patrimoniali,
                'overhead_expenses' => $row->oneri_e_supporto_generale,
                'total_expenses' => $row->totale_oneri,
                'general_interest_revenues' => $row->proventi_a_interesse_generale,
                'other_activities_revenues' => $row->proventi_b_attivita_diverse,
                'fundraising_revenues' => $row->proventi_c_raccolta_fondi,
                'financial_revenues' => $row->proventi_d_finanziarie_patrimoniali,
                'overhead_revenues' => $row->proventi_e_supporto_generale,
                'total_revenues' => $row->totale_proventi,
                'pre_tax_result' => $row->risultato_ante_imposte,
                'taxes' => $row->imposte,
                'net_result' => $row->risultato_esercizio,
            ];

            $existing = CaiFinancialStatement::query()
                ->where('cai_runts_registration_id', $row->id_runts)
                ->where('year', $attributes['year'])
                ->first();

            if ($existing === null) {
                CaiFinancialStatement::create($attributes);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * `cariche_sociali` è vuota nel dataset reale odierno (0 righe): questo percorso non
     * è quindi verificabile contro dati reali, solo contro la fixture dei test. Nessuna
     * chiave naturale nella fonte né vincolo unique a destinazione (US-801): la chiave di
     * dedup è la tupla (registrazione, ruolo, codice fiscale, valid_from) — ragionevole
     * per il dataset atteso, ma non garantita univoca da alcun vincolo DB.
     *
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<string, true>  $matchedIdRunts
     */
    private function importBoardMembers(Collection $rows, array $matchedIdRunts, bool $dryRun): CaiImportTableResult
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            if (! isset($matchedIdRunts[(string) $row->id_runts])) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                continue;
            }

            $fullName = trim(implode(' ', array_filter(
                [$row->nome, $row->cognome],
                fn (mixed $part): bool => $part !== null && trim((string) $part) !== '',
            )));
            $fullName = $fullName === '' ? null : $fullName;

            $validFrom = CaiRuntsDateParser::parse($row->valid_from);
            $validTo = CaiRuntsDateParser::parse($row->valid_to);

            $attributes = [
                'cai_runts_registration_id' => $row->id_runts,
                'role' => $row->ruolo,
                'full_name' => $fullName,
                'tax_code' => $row->codice_fiscale,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
            ];

            $existing = CaiBoardMember::query()
                ->where('cai_runts_registration_id', $row->id_runts)
                ->where('role', $row->ruolo)
                ->when(
                    $row->codice_fiscale === null,
                    fn ($query) => $query->whereNull('tax_code'),
                    fn ($query) => $query->where('tax_code', $row->codice_fiscale),
                )
                ->when(
                    $validFrom === null,
                    fn ($query) => $query->whereNull('valid_from'),
                    fn ($query) => $query->where('valid_from', $validFrom),
                )
                ->first();

            if ($existing === null) {
                CaiBoardMember::create($attributes);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                $existing->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @param  array<string, true>  $matchedIdRunts
     */
    private function importDocuments(Collection $rows, array $matchedIdRunts, string $datapackDir, bool $dryRun): CaiImportTableResult
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            if (! isset($matchedIdRunts[(string) $row->id_runts])) {
                $skipped++;

                continue;
            }

            if ($row->skip_reason !== null && trim((string) $row->skip_reason) !== '') {
                $skipped++;

                continue;
            }

            $sourcePath = $row->path !== null
                ? $datapackDir.'/'.ltrim((string) $row->path, '/')
                : null;

            if ($sourcePath === null || ! is_file($sourcePath)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                continue;
            }

            $fileName = $row->filename !== null ? basename((string) $row->filename) : basename($sourcePath);
            $destinationPath = "{$row->id_runts}/{$fileName}";

            $attributes = [
                'cai_runts_registration_id' => $row->id_runts,
                'document_type' => $row->tipo,
                'year' => $this->toInt($row->anno),
                'title' => $row->documento,
                'file_name' => $fileName,
                'mime_type' => $row->mime,
                'size' => $this->toInt($row->size),
                'hash' => $row->hash_sha256,
                'file_path' => $destinationPath,
            ];

            $existing = CaiDocument::query()
                ->where('cai_runts_registration_id', $row->id_runts)
                ->where('file_name', $fileName)
                ->first();

            if ($existing === null) {
                $this->copyDocumentFile($sourcePath, $destinationPath);
                CaiDocument::create($attributes);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                $this->copyDocumentFile($sourcePath, $destinationPath);
                $existing->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    private function copyDocumentFile(string $sourceAbsolutePath, string $destinationRelativePath): void
    {
        $stream = fopen($sourceAbsolutePath, 'rb');

        if ($stream === false) {
            return;
        }

        try {
            Storage::disk(self::DOCUMENTS_DISK)->put($destinationRelativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
