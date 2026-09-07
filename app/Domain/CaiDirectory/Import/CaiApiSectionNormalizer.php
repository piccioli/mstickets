<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Converte una riga grezza JSON dell'API pubblica CAI (`sections-list-simple`/
 * `sections/{code}/sub-sections-list`, design doc §3.1) nella stessa forma
 * `cai_*`-prefissata già usata dalle tabelle `sezioni_cai`/`sottosezioni_cai` del
 * datapack statico, così {@see CaiSectionFieldMapper} può mappare entrambe le
 * fonti senza saperne la provenienza. Nomi di campo API verificati nel
 * prototipo Python `RUNTS/scraper/cai_scraper.py`
 * (`_normalize_section`/`_normalize_subsection`).
 */
final class CaiApiSectionNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public static function normalizeSection(array $raw): object
    {
        return (object) [
            'codice_cai' => $raw['code'] ?? null,
            'cai_denominazione' => $raw['name'] ?? '',
            'cai_codice_fiscale' => $raw['cf'] ?? null,
            'cai_partita_iva' => $raw['vat'] ?? null,
            'cai_email' => $raw['email'] ?? null,
            'cai_pec' => $raw['pec'] ?? null,
            'cai_telefono_sede' => $raw['officePhone'] ?? null,
            'cai_telefono' => $raw['phone'] ?? null,
            'cai_fax' => $raw['fax'] ?? null,
            'cai_indirizzo_sede' => self::encodeAddress($raw['officeAddress'] ?? $raw['office_address'] ?? null),
            'cai_indirizzo_postale' => self::encodeAddress($raw['postalAddress'] ?? $raw['postal_address'] ?? null),
            'cai_sito_web' => $raw['website'] ?? null,
            'cai_orari' => $raw['timetable'] ?? null,
            'cai_avvisi' => $raw['notice'] ?? null,
            'cai_anno_fondazione' => $raw['foundationYear'] ?? null,
            'cai_soci_ultimo_anno' => $raw['lastyearMembershipsCount'] ?? null,
            'cai_lat' => $raw['latitude'] ?? null,
            'cai_lon' => $raw['longitude'] ?? null,
            'cai_regione' => mb_strtoupper((string) ($raw['region'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function normalizeSubsection(array $raw, string $sectionCode): object
    {
        return (object) [
            'cai_codice' => $raw['code'] ?? null,
            'cai_sezione_codice' => $sectionCode,
            'cai_nome' => $raw['name'] ?? '',
            'cai_email' => $raw['email'] ?? null,
            'cai_telefono_sede' => $raw['officePhone'] ?? null,
            'cai_telefono' => $raw['phone'] ?? null,
            'cai_indirizzo_sede' => self::encodeAddress($raw['officeAddress'] ?? $raw['office_address'] ?? null),
            'cai_sito_web' => $raw['website'] ?? null,
            'cai_orari' => $raw['timetable'] ?? null,
            'cai_avvisi' => $raw['notice'] ?? null,
            'cai_anno_fondazione' => $raw['foundationYear'] ?? null,
            'cai_soci' => $raw['currentMemberships'] ?? $raw['lastyearMembershipsCount'] ?? null,
            'cai_lat' => $raw['latitude'] ?? null,
            'cai_lon' => $raw['longitude'] ?? null,
        ];
    }

    private static function encodeAddress(mixed $address): ?string
    {
        return $address === null ? null : json_encode($address, JSON_THROW_ON_ERROR);
    }
}
