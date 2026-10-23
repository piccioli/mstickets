<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Converte il dettaglio grezzo di un rifugio dell'API rifugi.cai.it (`GET /api/v1/shelters/{id_cai}`, salvato tale e
 * quale in `snap_cai_huts.payload`) negli attributi di `cai_huts` (US-1031). La fonte espone i dati come lista
 * `fields` di coppie `{name, value, data}`: tutti i valori grezzi finiscono in `fields`, quelli usati dalle pagine sono
 * normalizzati nelle colonne dedicate. `None`/stringa vuota → `null`; tipo/stato sconosciuti → `null` (il testo resta
 * in `fields`).
 */
final class CaiHutFieldMapper
{
    /** Parole che, come valore di un servizio testuale, significano "assente". */
    private const NEGATIVE_WORDS = ['0', 'no', 'assente', 'nessuno', 'nessuna'];

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed> attributi di `cai_huts` senza `id_cai`
     */
    public static function map(array $detail): array
    {
        $fields = self::fieldsOf($detail);
        $coordinates = is_array($detail['geo']['coordinates'] ?? null) ? $detail['geo']['coordinates'] : [];

        return [
            'branch_code' => self::text($fields['branch_id'] ?? null),
            'branch_name' => self::text($fields['branch'] ?? null),
            'name' => self::text($detail['title'] ?? null) ?? '',
            'alias' => self::text($fields['alias'] ?? null),
            'slug' => self::text($detail['slug'] ?? null),
            'type' => CaiHutType::tryFrom((string) self::text($fields['type'] ?? null))?->value,
            'status' => CaiHutStatus::tryFrom((string) self::text($fields['status'] ?? null))?->value,
            'latitude' => self::coordinate($coordinates[1] ?? null),
            'longitude' => self::coordinate($coordinates[0] ?? null),
            'altitude' => self::integer($fields['altitude_geo'] ?? ($detail['altitude_geo'] ?? null)),
            'locality' => self::text($fields['locality_geo'] ?? null),
            'municipality' => self::text($fields['municipality_geo'] ?? null),
            'province' => self::text($fields['province_geo'] ?? null),
            'region' => self::text($fields['region_geo'] ?? null),
            'valley' => self::text($fields['valley_geo'] ?? null),
            'massif' => self::text($fields['massif_geo'] ?? null),
            'protected_area' => self::text($fields['protected_area_geo'] ?? null),
            'beds' => self::integer($fields['posti_letto_service'] ?? null) ?? self::integer($fields['posti_totali_service'] ?? null),
            'winter_beds' => self::integer($fields['posti_letto_invernali_service'] ?? null),
            'opening_times' => self::openingTimes($fields['openingTime_contact'] ?? null),
            'photos' => self::photos($detail['media'] ?? null),
            'services' => self::services($fields),
            'contacts' => self::contacts($fields),
            'management' => self::management($fields),
            'fields' => $fields,
            'source_updated_at' => self::timestamp($detail['updated_at'] ?? null),
        ];
    }

    /**
     * Canone della fonte in formato italiano ("48564,05", "17.080,00", "552,22 €") → numero; testo libero
     * ("gratuita", "7500 + IVA") → `null`.
     */
    public static function amount(mixed $value): ?float
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        $text = str_replace(['€', ' ', "\u{00A0}"], '', $text);

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $text) === 1) {
            $text = str_replace('.', '', $text);
        }

        if (preg_match('/^\d+(,\d+)?$/', $text) === 1) {
            return (float) str_replace(',', '.', $text);
        }

        return preg_match('/^\d+\.\d{1,2}$/', $text) === 1 ? (float) $text : null;
    }

    /** Data `Y-m-d` della fonte; qualunque altro formato → `null`. */
    public static function date(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) !== 1) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $text);

        return $date !== null && $date->format('Y-m-d') === $text ? $text : null;
    }

    /** Stringa ripulita; `null`, stringa vuota/spazi e il letterale "None" → `null`. */
    public static function text(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_bool($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' || $text === 'None' ? null : $text;
    }

    public static function integer(mixed $value): ?int
    {
        $text = self::text($value);

        return $text !== null && preg_match('/^-?\d+$/', $text) === 1 ? (int) $text : null;
    }

    /**
     * Valore di un servizio/certificazione come 1/0/`null`: numero > 0 → 1, 0 o parola negativa → 0, altro testo → 1.
     */
    public static function flag(mixed $value): ?int
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        if (is_numeric($text)) {
            return (float) $text > 0 ? 1 : 0;
        }

        return in_array(mb_strtolower($text), self::NEGATIVE_WORDS, true) ? 0 : 1;
    }

    /**
     * `fields` della fonte come mappa nome → valore (o il `data` strutturato, se il valore manca).
     *
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private static function fieldsOf(array $detail): array
    {
        $fields = [];

        foreach (is_array($detail['fields'] ?? null) ? $detail['fields'] : [] as $field) {
            if (! is_array($field) || ! isset($field['name'])) {
                continue;
            }

            $fields[(string) $field['name']] = $field['value'] ?? ($field['data'] ?? null);
        }

        ksort($fields);

        return $fields;
    }

    private static function coordinate(mixed $value): ?float
    {
        return is_numeric($value) && abs((float) $value) < 1000 ? round((float) $value, 7) : null;
    }

    private static function timestamp(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($text)->utc()->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Periodi di apertura (`openingTime_contact.data`): `[{types: [...], start_date, end_date}]`, `null` se nessuno.
     *
     * @return list<array{types: list<string>, start_date: ?string, end_date: ?string}>|null
     */
    private static function openingTimes(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        $periods = [];

        foreach ($data as $period) {
            $attributes = is_array($period['attributes'] ?? null) ? $period['attributes'] : [];
            $rawTypes = $attributes['type'] ?? null;
            $decoded = is_string($rawTypes) ? json_decode($rawTypes, true) : $rawTypes;
            $types = is_array($decoded) ? $decoded : [$rawTypes];

            $periods[] = [
                'types' => array_values(array_filter(array_map(self::text(...), $types), fn (?string $t): bool => $t !== null)),
                'start_date' => self::date($attributes['startDate'] ?? null),
                'end_date' => self::date($attributes['endDate'] ?? null),
            ];
        }

        return $periods === [] ? null : $periods;
    }

    /**
     * URL delle foto (`media[].original_url`) nell'ordine della fonte.
     *
     * @return list<string>
     */
    private static function photos(mixed $media): array
    {
        if (! is_array($media)) {
            return [];
        }

        usort($media, fn ($a, $b): int => ((int) ($a['order_column'] ?? 0)) <=> ((int) ($b['order_column'] ?? 0)));

        $urls = [];

        foreach ($media as $item) {
            $url = is_array($item) ? self::text($item['original_url'] ?? null) : null;

            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Tutti i campi `*_service` (tranne i conteggi `posti_*`) come chiave senza suffisso → 1/0/`null`.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, int|null>
     */
    private static function services(array $fields): array
    {
        $services = [];

        foreach ($fields as $name => $value) {
            if (! str_ends_with($name, '_service') || str_starts_with($name, 'posti_')) {
                continue;
            }

            $services[substr($name, 0, -strlen('_service'))] = self::flag($value);
        }

        return $services;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, string|null>
     */
    private static function contacts(array $fields): array
    {
        return [
            'phone' => self::text($fields['fixedPhone_contact'] ?? null),
            'mobile_phone' => self::text($fields['mobilePhone_contact'] ?? null),
            'email' => self::text($fields['emailAddress'] ?? null),
            'website' => self::text($fields['webAddress_contact'] ?? null),
            'facebook' => self::text($fields['facebook_contact'] ?? null),
            'instagram' => self::text($fields['instagram_contact'] ?? null),
            'booking_url' => self::text($fields['prenotation_link_contact'] ?? null),
        ];
    }

    /**
     * Dati di gestione (riservati, vedi `CaiHutPolicy::viewManagement`). Il contratto viene dai campi
     * `contract_*_management_property`, con ripiego sui `contract_*_property` quando mancano.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function management(array $fields): array
    {
        $contract = fn (string $key): mixed => self::text($fields["contract_{$key}_management_property"] ?? null) ?? ($fields["contract_{$key}_property"] ?? null);
        $certifications = [];

        foreach ($fields as $name => $value) {
            if (str_ends_with($name, '_certification')) {
                $text = self::text($value);
                $certifications[substr($name, 0, -strlen('_certification'))] = in_array($text, ['0', '1'], true) ? (int) $text : $text;
            }
        }

        return [
            'manager' => [
                'company' => self::text($fields['company_management_property'] ?? null),
                'name' => self::text($fields['name_management_property'] ?? null),
                'surname' => self::text($fields['surname_management_property'] ?? null),
                'tax_code' => self::text($fields['taxCode_management_property'] ?? null),
                'mobile_phone' => self::text($fields['mobilePhone_management_property'] ?? null),
                'phone' => self::text($fields['fixedPhone_management_property'] ?? null),
                'email' => self::text($fields['email_management_property'] ?? null),
                'pec' => self::text($fields['pec_management_property'] ?? null),
                'website' => self::text($fields['webSite_management_property'] ?? null),
            ],
            'possession_type' => self::text($fields['possession_type_management_property'] ?? null),
            'contract' => [
                'start_date' => self::date($contract('start_date')),
                'end_date' => self::date($contract('end_date')),
                'fee' => self::amount($contract('fee')),
                'fee_text' => self::text($contract('fee')),
            ],
            'property' => [
                'name' => self::text($fields['name_property'] ?? null),
                'tax_code' => self::text($fields['taxCode_property'] ?? null),
                'possession_type' => self::text($fields['possession_type_property'] ?? null),
                'phone' => self::text($fields['fixedPhone_property'] ?? null),
                'email' => self::text($fields['email_property'] ?? null),
                'pec' => self::text($fields['pec_property'] ?? null),
                'website' => self::text($fields['webSite_property'] ?? null),
            ],
            'certifications' => $certifications,
            'cin' => self::text($fields['shelter_cin'] ?? null),
        ];
    }
}
