<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration;

/**
 * Mappatura pura riga→attributi per `CaiRuntsRegistration`/`CaiBoardMember` (Fase 9, storia 3): estratta da
 * {@see CaiDatapackImporter} perché va condivisa, non duplicata, tra l'import da datapack statico
 * (`importRegistrations()`/`importBoardMembers()`) e lo scrape live
 * ({@see SyncCaiRuntsRegistration}) — stesso principio già applicato a
 * {@see CaiSectionFieldMapper} in Fase 9 storia 1. `lat`/`lon` sono presenti solo sulla riga del datapack
 * (mai geocodificati dallo scrape RUNTS live): letti con `?? null`, mai un accesso diretto a proprietà che
 * potrebbe non esistere sull'oggetto costruito dalla risposta JSON del servizio live.
 */
final class CaiRuntsRegistrationFieldMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function mapRegistration(object $row, string $sectionCode): array
    {
        return [
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
            'latitude' => CaiSectionFieldMapper::toCoordinate($row->lat ?? null),
            'longitude' => CaiSectionFieldMapper::toCoordinate($row->lon ?? null),
            'registration_date' => CaiRuntsDateParser::parse($row->data_iscrizione),
            'register_section' => $row->sezione_registro,
            'activity_sectors' => $row->settori_attivita,
            'legal_representative' => $row->rappresentante_legale,
            'website' => $row->sito_web,
            'pec' => $row->pec,
            'official_page_url' => $row->url_dettaglio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapBoardMember(object $row, string $idRunts): array
    {
        $fullName = trim(implode(' ', array_filter(
            [$row->nome ?? null, $row->cognome ?? null],
            fn (mixed $part): bool => $part !== null && trim((string) $part) !== '',
        )));
        $fullName = $fullName === '' ? null : $fullName;

        return [
            'cai_runts_registration_id' => $idRunts,
            'role' => $row->ruolo,
            'full_name' => $fullName,
            'tax_code' => $row->codice_fiscale ?? null,
            'valid_from' => CaiRuntsDateParser::parse($row->valid_from ?? null),
            'valid_to' => CaiRuntsDateParser::parse($row->valid_to ?? null),
        ];
    }
}
