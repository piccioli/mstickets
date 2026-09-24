<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use Illuminate\Support\Str;

/**
 * Mappatura pura riga→attributi per `CaiSection`/`CaiSubsection` (Fase 9, storia 1):
 * estratta da {@see CaiDatapackImporter} perché va condivisa, non duplicata, tra
 * l'import da datapack statico ({@see CaiDatapackImporter::importSections()}/
 * `importSubsections()`) e lo scrape live ({@see SyncCaiSectionAndSubsections}) —
 * design doc §3.1. L'input `object $row` ha SEMPRE le stesse proprietà `cai_*`
 * indipendentemente dalla fonte: per il datapack sono le colonne native di
 * `sezioni_cai`/`sottosezioni_cai`; per l'API live, {@see CaiApiSectionNormalizer}
 * produce un oggetto con la stessa identica forma prima di passarlo qui — questo
 * mapper non sa mai da dove viene la riga.
 */
final class CaiSectionFieldMapper
{
    /**
     * @param  array<string, int>  $usersByLowerEmail
     * @return array<string, mixed>
     */
    public static function mapSection(object $row, array $usersByLowerEmail): array
    {
        return [
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
            'founded_year' => self::toInt($row->cai_anno_fondazione),
            'members_count' => self::toInt($row->cai_soci_ultimo_anno),
            'latitude' => self::toCoordinate($row->cai_lat),
            'longitude' => self::toCoordinate($row->cai_lon),
            'region' => $row->cai_regione,
            'user_id' => self::matchUserId($row->cai_email, $usersByLowerEmail),
        ];
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     * @return array<string, mixed>
     */
    public static function mapSubsection(object $row, array $usersByLowerEmail): array
    {
        return [
            'cai_section_id' => $row->cai_sezione_codice,
            'name' => $row->cai_nome,
            'email' => $row->cai_email,
            'phone_office' => $row->cai_telefono_sede,
            'phone' => $row->cai_telefono,
            'address' => CaiRuntsAddressFormatter::format($row->cai_indirizzo_sede),
            'website' => $row->cai_sito_web,
            'office_hours' => $row->cai_orari,
            'notices' => $row->cai_avvisi,
            'founded_year' => self::toInt($row->cai_anno_fondazione),
            'members_count' => self::toInt($row->cai_soci),
            'latitude' => self::toCoordinate($row->cai_lat),
            'longitude' => self::toCoordinate($row->cai_lon),
            'user_id' => self::matchUserId($row->cai_email, $usersByLowerEmail),
        ];
    }

    public static function toInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * `cai_sections.latitude`/`longitude` sono `decimal(10,7)`: al massimo 3 cifre intere,
     * un valore |x| >= 1000 farebbe fallire l'insert con "numeric field overflow" (visto
     * sul dataset reale, US-802: una riga con `cai_lat = 25614`). Scartarlo a `null`
     * invece di far fallire l'intero import per una sezione.
     */
    public static function toCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $float = (float) $value;

        return abs($float) < 1000.0 ? $float : null;
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    public static function matchUserId(?string $email, array $usersByLowerEmail): ?int
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return $usersByLowerEmail[Str::lower(trim($email))] ?? null;
    }
}
