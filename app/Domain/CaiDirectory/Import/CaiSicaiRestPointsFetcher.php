<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Scarica i punti di appoggio del Sentiero Italia CAI dall'API pubblica rifugi.cai.it (US-1092) nella tabella
 * `snap_cai_sicai_rest_points` del datapack, poi letta da {@see CaiSicaiRestPointsImporter}: elenco
 * `/sicaiRestPoints?page=` (15 per pagina, `per_page` ignorato dalla fonte) + dettaglio `/sicaiRestPoints/{id}`.
 * ~330 richieste (ott. 2026: 307 punti, 21 pagine): con `cai:fetch-huts` subito prima si supera il rate limit, ma il
 * fetcher aspetta il `Retry-After`.
 */
final class CaiSicaiRestPointsFetcher extends CaiRifugiApiFetcher
{
    public function table(): string
    {
        return CaiSicaiRestPointsImporter::TABLE;
    }

    protected function listPath(): string
    {
        return '/sicaiRestPoints';
    }

    protected function idKey(): string
    {
        return 'id';
    }

    protected function label(): string
    {
        return 'punti di appoggio SICAI';
    }
}
