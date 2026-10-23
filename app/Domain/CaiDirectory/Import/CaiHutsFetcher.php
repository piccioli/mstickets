<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Scarica i rifugi dall'API pubblica rifugi.cai.it (US-1032) nella tabella `snap_cai_huts` del datapack, poi letta da
 * {@see CaiHutsImporter}: elenco `/shelters?per_page=&page=` + dettaglio `/shelters/{id_cai}`. Un run completo (~770
 * richieste) sta nel rate limit dell'API; due run di fila no (vedi {@see CaiRifugiApiFetcher}).
 */
final class CaiHutsFetcher extends CaiRifugiApiFetcher
{
    public function table(): string
    {
        return CaiHutsImporter::TABLE;
    }

    protected function listPath(): string
    {
        return '/shelters';
    }

    protected function idKey(): string
    {
        return 'id_cai';
    }

    protected function label(): string
    {
        return 'rifugi';
    }

    protected function listQuery(): array
    {
        return ['per_page' => config('cai_directory.huts_api.per_page')];
    }
}
