<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Formattazione degli indirizzi del datapack RUNTS-CAI (US-802). `sezioni_cai.cai_indirizzo_sede`/
 * `cai_indirizzo_postale` e gli equivalenti di `sottosezioni_cai` NON sono testo semplice: sul
 * dataset reale il 100% delle righe è un oggetto JSON di geocoding (`address1`, `address2`,
 * `number`, `zip`, `city`, `province`, `nation`, più metadati di qualità del match che qui non
 * servono). Inserire quella stringa JSON grezza (fino a ~300 caratteri) nella colonna
 * `address`/`postal_address` (`string(255)`, US-801) causa un troncamento SQL su Postgres
 * ("value too long for type character varying(255)"): questo formatter produce invece una singola
 * riga leggibile (max osservato 142 caratteri sul dataset reale), riusando lo stesso principio di
 * {@see CaiRuntsDateParser}: non sollevare mai un'eccezione su un valore sorgente inatteso, solo
 * un fallback ragionevole (stringa grezza troncata o null).
 */
final class CaiRuntsAddressFormatter
{
    private const MAX_LENGTH = 255;

    public static function format(?string $rawJson): ?string
    {
        if ($rawJson === null || trim($rawJson) === '') {
            return null;
        }

        $decoded = json_decode($rawJson, true);

        if (! is_array($decoded)) {
            return mb_substr(trim($rawJson), 0, self::MAX_LENGTH);
        }

        $street = trim(
            trim((string) ($decoded['address1'] ?? ''))
            .(($decoded['number'] ?? '') !== '' ? ' '.$decoded['number'] : '')
        );

        if (($decoded['address2'] ?? '') !== '') {
            $street = $street === '' ? (string) $decoded['address2'] : $street.' - '.$decoded['address2'];
        }

        $locality = trim(trim((string) ($decoded['zip'] ?? '')).' '.trim((string) ($decoded['city'] ?? '')));

        if (($decoded['province'] ?? '') !== '') {
            $locality = trim($locality.' ('.$decoded['province'].')');
        }

        $nation = trim((string) ($decoded['nation'] ?? ''));

        $parts = array_values(array_filter([$street, $locality, $nation], fn (string $part): bool => $part !== ''));

        if ($parts === []) {
            $rawAddress = trim((string) ($decoded['rawAddress'] ?? ''));

            return $rawAddress === '' ? null : mb_substr($rawAddress, 0, self::MAX_LENGTH);
        }

        return mb_substr(implode(', ', $parts), 0, self::MAX_LENGTH);
    }
}
