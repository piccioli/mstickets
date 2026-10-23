<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupSicai;

/**
 * Aspetto di un tipo di punto di appoggio SICAI (10.9): colore, icona, lettera del marker e nomi, dalla tabella `TIPI`
 * del design "Tappe SICAI del GR" per i sei tipi previsti (Rifugio, Hotel, B&B, Casa vacanze, Ostello, Campeggio). Il
 * tipo è testo libero della fonte (rifugi.cai.it, `fields.type`): i tipi fuori dal design (Bivacco, Agriturismo, …)
 * hanno un colore e un nome propri, quelli sconosciuti il grigio di "Altro". Il tipo vuoto arriva già come
 * {@see CaiRegionalGroupSicai::OTHER_TYPE}.
 */
final readonly class CaiSicaiRestPointType
{
    /** Icone del design (path SVG su viewBox 24, tratto bianco). */
    private const string ICON_HUT = 'M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5';

    private const string ICON_BED = 'M3 19V6M3 14h18v5M21 14v-2a3 3 0 0 0-3-3h-7v5M7 11.5h.01';

    private const string ICON_CUP = 'M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5zM17 10h1.5a2.5 2.5 0 0 1 0 5H17M8 3v3M12 3v3';

    private const string ICON_KEY = 'M15 4a5 5 0 1 1-4.6 7L4 17.4V20h3v-2h2v-2h2l.6-.6A5 5 0 0 1 15 4zM16 8h.01';

    private const string ICON_PEOPLE = 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 12 0v1M16 3.5a4 4 0 0 1 0 7M22 21v-1a6 6 0 0 0-4-5.6';

    private const string ICON_TENT = 'M2 20h20M12 4L3.5 20M12 4l8.5 16M12 13l-3 7M12 13l3 7';

    /** tipo della fonte → [colore, icona, lettera, etichetta, plurale, singolare minuscolo, plurale minuscolo] */
    private const array TYPES = [
        'Rifugio' => ['#0f7d63', self::ICON_HUT, 'R', 'Rifugio', 'Rifugi', 'rifugio', 'rifugi'],
        'Hotel' => ['#2563c9', self::ICON_BED, 'H', 'Hotel', 'Hotel', 'hotel', 'hotel'],
        'B&B' => ['#a24cc7', self::ICON_CUP, 'B', 'B&B', 'B&B', 'B&B', 'B&B'],
        'Casa vacanze' => ['#c77e2a', self::ICON_KEY, 'C', 'Casa vacanze', 'Case vacanze', 'casa vacanze', 'case vacanze'],
        'Ostello' => ['#5b6475', self::ICON_PEOPLE, 'O', 'Ostello', 'Ostelli', 'ostello', 'ostelli'],
        'Camping' => ['#4f8a2a', self::ICON_TENT, 'K', 'Campeggio', 'Campeggi', 'campeggio', 'campeggi'],
        'Bivacco' => ['#d9822b', self::ICON_HUT, 'V', 'Bivacco', 'Bivacchi', 'bivacco', 'bivacchi'],
        'Agriturismo' => ['#7a8f2a', self::ICON_HUT, 'A', 'Agriturismo', 'Agriturismi', 'agriturismo', 'agriturismi'],
        'Locanda' => ['#1f7a9c', self::ICON_BED, 'L', 'Locanda', 'Locande', 'locanda', 'locande'],
        'Residence' => ['#3f5fa8', self::ICON_KEY, 'S', 'Residence', 'Residence', 'residence', 'residence'],
        'Foresteria' => ['#8a6d3b', self::ICON_BED, 'F', 'Foresteria', 'Foresterie', 'foresteria', 'foresterie'],
        'Ristorante' => ['#b4503c', self::ICON_CUP, 'T', 'Ristorante', 'Ristoranti', 'ristorante', 'ristoranti'],
    ];

    private const string OTHER_COLOR = '#8a93a3';

    public function __construct(public string $type) {}

    public static function of(string $type): self
    {
        return new self($type);
    }

    public function color(): string
    {
        return self::TYPES[$this->type][0] ?? self::OTHER_COLOR;
    }

    /** Path SVG dell'icona (viewBox 0 0 24 24). */
    public function icon(): string
    {
        return self::TYPES[$this->type][1] ?? self::ICON_HUT;
    }

    /** Lettera del marker sulla mappa. */
    public function letter(): string
    {
        return self::TYPES[$this->type][2] ?? mb_strtoupper(mb_substr($this->type, 0, 1));
    }

    /** "Campeggio" per "Camping"; gli altri come nella fonte. */
    public function label(): string
    {
        return self::TYPES[$this->type][3] ?? $this->type;
    }

    /** Titolo del gruppo e del filtro: "Rifugi", "Case vacanze". */
    public function plural(): string
    {
        return self::TYPES[$this->type][4] ?? $this->type;
    }

    /** "110 rifugi", "1 casa vacanze": minuscolo, singolare o plurale. */
    public function count(int $count): string
    {
        $entry = self::TYPES[$this->type] ?? null;
        $word = $entry === null ? mb_strtolower($this->type) : ($count === 1 ? $entry[5] : $entry[6]);

        return $count.' '.$word;
    }

    /** Chiave nell'URL del filtro per tipo (`?tipo=casa-vacanze`). */
    public function key(): string
    {
        return self::keyOf($this->type);
    }

    public static function keyOf(string $type): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(str_replace('&', '', $type))), '-');
    }
}
