<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Converte il dettaglio grezzo di un punto di appoggio SICAI (`GET /api/v1/sicaiRestPoints/{id}`, salvato tale e quale
 * in `snap_cai_sicai_rest_points.payload`) negli attributi di `cai_sicai_rest_points` (US-1092). Stessa forma dei
 * rifugi (lista `fields` `{name, value, data}`, `geo` Point): riusa gli helper di {@see CaiHutFieldMapper}. Il
 * collegamento al rifugio (`shelter_id` = `id` interno della fonte, non `id_cai`) lo risolve l'importer.
 */
final class CaiSicaiRestPointFieldMapper
{
    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed> attributi di `cai_sicai_rest_points` senza `id` e `cai_hut_id_cai`
     */
    public static function map(array $detail): array
    {
        $fields = CaiHutFieldMapper::fieldsOf($detail);
        $coordinates = is_array($detail['geo']['coordinates'] ?? null) ? $detail['geo']['coordinates'] : [];
        $street = CaiHutFieldMapper::text($fields['address_street'] ?? null);
        $number = CaiHutFieldMapper::text($fields['address_number'] ?? null);

        return [
            'name' => CaiHutFieldMapper::text($detail['title'] ?? null) ?? '',
            'type' => CaiHutFieldMapper::text($fields['type'] ?? null),
            'latitude' => CaiHutFieldMapper::coordinate($coordinates[1] ?? null),
            'longitude' => CaiHutFieldMapper::coordinate($coordinates[0] ?? null),
            'region' => CaiHutFieldMapper::text($fields['region_geo'] ?? null),
            'municipality' => CaiHutFieldMapper::text($fields['municipality_geo'] ?? null),
            'address' => $street === null ? null : trim($street.' '.($number ?? '')),
            'operator' => CaiHutFieldMapper::text($fields['operator'] ?? null),
            'website' => CaiHutFieldMapper::text($fields['website'] ?? null),
            'email' => CaiHutFieldMapper::text($fields['email_management_property'] ?? null),
            'phone' => CaiHutFieldMapper::text($fields['mobilePhone_management_property'] ?? null)
                ?? CaiHutFieldMapper::text($fields['fixedPhone_management_property'] ?? null),
            'fields' => $fields,
            'source_updated_at' => CaiHutFieldMapper::timestamp($detail['updated_at'] ?? null),
        ];
    }

    /**
     * `shelter_id` della fonte (id interno del rifugio in rifugi.cai.it), `null` se assente.
     *
     * @param  array<string, mixed>  $detail
     */
    public static function shelterId(array $detail): ?int
    {
        return CaiHutFieldMapper::integer($detail['shelter_id'] ?? null);
    }
}
