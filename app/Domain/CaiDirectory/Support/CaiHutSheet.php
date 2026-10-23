<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiHutContractStatus;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Support\Formatting\ItalianNumberFormatter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Dati della scheda di un rifugio (US-1035) già pronti per la vista, come nel design
 * `docs/project/design/fase-10/rifugi-di-sezione.dc.html` §2: un valore assente è `null` (la vista lo mostra "—").
 * I dati di gestione ({@see manager()}, contratto, {@see property()}, {@see certifications()}) sono riservati: la
 * pagina li rende solo se `CaiHutPolicy::viewManagement` lo consente.
 */
final readonly class CaiHutSheet
{
    /**
     * I 15 servizi del design: chiave di `cai_huts.services` → etichetta e icona (path SVG 24×24 del design).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array SERVICES = [
        'acqua_in_rifugio' => ['Acqua in rifugio', 'M12 3s6 7 6 11a6 6 0 0 1-12 0c0-4 6-11 6-11z'],
        'acqua_esterno' => ['Acqua esterna', 'M4 9h9a3 3 0 0 1 3 3v2M8 9V5h4M16 18v.01M16 21v.01'],
        'elettricita' => ['Elettricità', 'M13 2L4 14h7l-1 8 9-12h-7z'],
        'wifi' => ['Wi-Fi', 'M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 19.5v.01'],
        'segnale_gsm' => ['Segnale GSM', 'M5 20v-4M10 20v-8M15 20V8M20 20V4'],
        'pagamento_pos' => ['Pagamento POS', 'M3 6h18v12H3zM3 10h18M7 15h4'],
        'defibrillatore' => ['Defibrillatore', 'M12 20s-8-5-8-11a4 4 0 0 1 8-1 4 4 0 0 1 8 1c0 6-8 11-8 11zM12.5 8l-2 4h3l-2 4'],
        'ristorante' => ['Ristorante', 'M7 3v18M4 3v5a3 3 0 0 0 6 0V3M17 3c-2 0-3 3-3 6s1 4 3 4v8'],
        'accesso_alla_cucina' => ['Accesso cucina', 'M4 10h16v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4zM2 10h20M9 5v2M12 4v3M15 5v2'],
        'docce' => ['Docce', 'M4 20V8a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4M10 10h8M12 14v.01M15 14v.01M18 14v.01M12 17v.01M15 17v.01M18 17v.01'],
        'accessibilita_ai_disabili' => ['Accessibilità disabili', 'M10 4v.01M10 8v6h6l2 5M14 18a4 4 0 1 1-6-5'],
        'accessibilita_famiglie_con_bambini' => ['Famiglie con bambini', 'M8 5v.01M16 8v.01M5 21v-7l3-4 3 4v7M14 21v-5l2-3 2 3v5'],
        'accessibilita_mountain_bike' => ['Mountain bike', 'M2 17a3 3 0 1 0 6 0a3 3 0 1 0-6 0M16 17a3 3 0 1 0 6 0a3 3 0 1 0-6 0M5 17l4-7h6l4 7M9 10l3 7M13 6h3'],
        'accessibilita_macchina' => ['Raggiungibile in auto', 'M3 16v-3l2-5h14l2 5v3zM4 16v2M20 16v2M7 13v.01M17 13v.01'],
        'ammissibilita_animali_domestici' => ['Animali ammessi', 'M8 9v.01M12 7v.01M16 9v.01M6 13v.01M18 13v.01M12 12c-3 0-5 4-5 6s2 2 5 2 5 0 5-2-2-6-5-6z'],
    ];

    /**
     * Certificazioni e impianti (chiave di `management.certifications` → etichetta). Il design ne disegna 8 di
     * fantasia: qui le 8 voci sì/no che la fonte ha davvero (depuratore, solare termico ed Ecolabel non esistono in
     * rifugi.cai.it; al loro posto separatore oli, acqua piovana e certificazione green).
     *
     * @var array<string, string>
     */
    public const array CERTIFICATIONS = [
        'iso14001' => 'ISO 14001',
        'photovoltaic' => 'Fotovoltaico',
        'powerGenerator' => 'Generatore',
        'hydroelectric' => 'Idroelettrico',
        'recycling' => 'Raccolta differenziata',
        'oilSeparator' => 'Separatore oli',
        'rain_water' => 'Recupero acqua piovana',
        'greenCertification' => 'Certificazione green',
    ];

    private const array MONTHS = ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto',
        'settembre', 'ottobre', 'novembre', 'dicembre'];

    public function __construct(public CaiHut $hut) {}

    /** Prima foto (URL di rifugi.cai.it), `null` se il rifugio non ne ha. */
    public function photo(): ?string
    {
        $photo = ($this->hut->photos ?? [])[0] ?? null;

        return is_string($photo) && $photo !== '' ? $photo : null;
    }

    /** Stato: etichetta dell'enum o, se la fonte ha un valore sconosciuto (es. "Rudere"), il testo grezzo. */
    public function statusLabel(): ?string
    {
        return $this->hut->status?->getLabel() ?? self::text(($this->hut->fields ?? [])['status'] ?? null);
    }

    public function altitude(): ?string
    {
        return $this->hut->altitude === null ? null : ItalianNumberFormatter::integer($this->hut->altitude);
    }

    public function beds(): ?string
    {
        return $this->hut->beds === null ? null : ItalianNumberFormatter::integer($this->hut->beds);
    }

    public function winterBeds(): ?string
    {
        return $this->hut->winter_beds === null ? null : ItalianNumberFormatter::integer($this->hut->winter_beds);
    }

    /** "46,16970° N · 10,87640° E" (gradi decimali, 5 cifre, virgola). */
    public function coordinates(): ?string
    {
        if ($this->hut->latitude === null || $this->hut->longitude === null) {
            return null;
        }

        $latitude = (float) $this->hut->latitude;
        $longitude = (float) $this->hut->longitude;

        return self::degrees($latitude).'° '.($latitude < 0 ? 'S' : 'N')
            .' · '.self::degrees($longitude).'° '.($longitude < 0 ? 'O' : 'E');
    }

    /**
     * "Dove si trova".
     *
     * @return list<array{label: string, value: ?string}>
     */
    public function location(): array
    {
        return [
            ['label' => 'Località', 'value' => $this->hut->locality],
            ['label' => 'Comune', 'value' => $this->hut->municipality],
            ['label' => 'Provincia', 'value' => $this->hut->province],
            ['label' => 'Regione', 'value' => $this->hut->region],
            ['label' => 'Valle', 'value' => $this->hut->valley],
            ['label' => 'Massiccio', 'value' => $this->hut->massif],
            ['label' => 'Area protetta', 'value' => $this->hut->protected_area],
            ['label' => 'Coordinate', 'value' => $this->coordinates()],
        ];
    }

    /**
     * Griglia dei 15 servizi: `value` 1 = Sì, 0 = No, `null` = dato assente ("—").
     *
     * @return list<array{label: string, icon: string, value: ?int}>
     */
    public function services(): array
    {
        $services = $this->hut->services ?? [];
        $rows = [];

        foreach (self::SERVICES as $key => [$label, $icon]) {
            $rows[] = ['label' => $label, 'icon' => $icon, 'value' => self::flag($services[$key] ?? null)];
        }

        return $rows;
    }

    /**
     * Contatti con il link (`tel:`, `mailto:`, URL) quando il valore c'è.
     *
     * @return list<array{label: string, value: ?string, href: ?string}>
     */
    public function contacts(): array
    {
        $contacts = $this->hut->contacts ?? [];
        $phone = self::text($contacts['phone'] ?? null) ?? self::text($contacts['mobile_phone'] ?? null);
        $email = self::text($contacts['email'] ?? null);

        return [
            ['label' => 'Telefono', 'value' => $phone, 'href' => $phone === null ? null : 'tel:'.preg_replace('/[^\d+]/', '', $phone)],
            ['label' => 'Email', 'value' => $email, 'href' => $email === null ? null : 'mailto:'.$email],
            self::link('Sito web', self::text($contacts['website'] ?? null), null),
            self::link('Facebook', self::text($contacts['facebook'] ?? null), 'https://facebook.com/'),
            self::link('Instagram', self::text($contacts['instagram'] ?? null), 'https://instagram.com/'),
        ];
    }

    /** Periodi di apertura: "Estiva: 23 maggio – 2 novembre 2026 · Fine settimana". */
    public function openingTimes(): ?string
    {
        $periods = [];

        foreach ($this->hut->opening_times ?? [] as $period) {
            $types = array_values(array_filter(
                is_array($period['types'] ?? null) ? $period['types'] : [],
                fn (mixed $type): bool => is_string($type) && $type !== '',
            ));
            $dates = self::dateRange($period['start_date'] ?? null, $period['end_date'] ?? null);

            if ($types === [] && $dates === null) {
                continue;
            }

            $text = $types === [] ? 'Apertura' : mb_strtoupper(mb_substr($types[0], 0, 1)).mb_substr(implode(' e ', $types), 1);
            $periods[] = $dates === null ? $text : $text.': '.$dates;
        }

        return $periods === [] ? null : implode(' · ', $periods);
    }

    /** Pagina pubblica del rifugio su rifugi.cai.it. */
    public function sourceUrl(): string
    {
        return 'https://rifugi.cai.it/shelters/'.$this->hut->id_cai;
    }

    /** "12 settembre 2026": ultimo aggiornamento del dato sulla fonte. */
    public function lastUpdate(): ?string
    {
        $updated = $this->hut->source_updated_at;

        return $updated === null ? null : self::longDate($updated->copy()->setTimezone((string) config('app.timezone')));
    }

    /** Il gestore ha almeno uno dei dati mostrati in {@see manager()}. */
    public function hasManager(): bool
    {
        return array_filter($this->manager(), fn (array $row): bool => $row['value'] !== null) !== [];
    }

    /**
     * Gestore: società, nome e cognome, telefono (cellulare, poi fisso), email, PEC.
     *
     * @return list<array{label: string, value: ?string}>
     */
    public function manager(): array
    {
        $manager = $this->managerData();
        $fullName = trim(implode(' ', array_filter([self::text($manager['name'] ?? null), self::text($manager['surname'] ?? null)])));

        return [
            ['label' => 'Società', 'value' => self::text($manager['company'] ?? null)],
            ['label' => 'Nome e cognome', 'value' => $fullName === '' ? null : $fullName],
            ['label' => 'Telefono', 'value' => self::text($manager['mobile_phone'] ?? null) ?? self::text($manager['phone'] ?? null)],
            ['label' => 'Email', 'value' => self::text($manager['email'] ?? null)],
            ['label' => 'PEC', 'value' => self::text($manager['pec'] ?? null)],
        ];
    }

    /** Tipo di possesso del gestore, con ripiego su quello indicato dalla proprietà. */
    public function possessionType(): ?string
    {
        $management = $this->hut->management ?? [];

        return self::text($management['possession_type'] ?? null)
            ?? self::text(is_array($management['property'] ?? null) ? ($management['property']['possession_type'] ?? null) : null);
    }

    public function contractStart(): ?string
    {
        return self::shortDate($this->contractDate('start_date'));
    }

    public function contractEnd(): ?string
    {
        return self::shortDate($this->contractDate('end_date'));
    }

    /** Canone annuo: "€ 48.500" (o con i centesimi se ci sono); testo libero della fonte se non è un numero. */
    public function fee(): ?string
    {
        $contract = $this->contract();
        $fee = $contract['fee'] ?? null;

        if (is_int($fee) || is_float($fee)) {
            $decimals = round((float) $fee, 2) === round((float) $fee) ? 0 : 2;

            return '€ '.ItalianNumberFormatter::decimal($fee, $decimals);
        }

        return self::text($contract['fee_text'] ?? null);
    }

    /** `null` se il contratto non ha una data di scadenza. */
    public function contractStatus(CarbonInterface $today): ?CaiHutContractStatus
    {
        $days = $this->daysLeft($today);

        return $days === null ? null : CaiHutContractStatus::fromDaysLeft($days);
    }

    /** "Scaduto da 4 mesi" / "Scade tra 12 giorni", solo per un contratto scaduto o in scadenza. */
    public function contractNote(CarbonInterface $today): ?string
    {
        $days = $this->daysLeft($today);
        $status = $days === null ? null : CaiHutContractStatus::fromDaysLeft($days);

        if ($days === null || $status === null || ! $status->isAlert()) {
            return null;
        }

        if ($days === 0) {
            return 'Scade oggi';
        }

        $months = (int) round(abs($days) / 30.44);
        $span = $months >= 1
            ? $months.' '.($months === 1 ? 'mese' : 'mesi')
            : abs($days).' '.(abs($days) === 1 ? 'giorno' : 'giorni');

        return $status === CaiHutContractStatus::Expired ? 'Scaduto da '.$span : 'Scade tra '.$span;
    }

    /**
     * Proprietà: nome, codice fiscale e codice CIN del rifugio.
     *
     * @return list<array{label: string, value: ?string}>
     */
    public function property(): array
    {
        $management = $this->hut->management ?? [];
        $property = is_array($management['property'] ?? null) ? $management['property'] : [];

        return [
            ['label' => 'Nome', 'value' => self::text($property['name'] ?? null)],
            ['label' => 'Codice fiscale', 'value' => self::text($property['tax_code'] ?? null)],
            ['label' => 'Codice CIN', 'value' => self::text($management['cin'] ?? null)],
        ];
    }

    /**
     * @return list<array{label: string, value: ?int}>
     */
    public function certifications(): array
    {
        $management = $this->hut->management ?? [];
        $certifications = is_array($management['certifications'] ?? null) ? $management['certifications'] : [];
        $rows = [];

        foreach (self::CERTIFICATIONS as $key => $label) {
            $rows[] = ['label' => $label, 'value' => self::flag($certifications[$key] ?? null)];
        }

        return $rows;
    }

    /** "Sì" / "No" / "—" di un servizio o di una certificazione. */
    public static function flagLabel(?int $value): string
    {
        return match ($value) {
            1 => 'Sì',
            0 => 'No',
            default => '—',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function managerData(): array
    {
        $management = $this->hut->management ?? [];

        return is_array($management['manager'] ?? null) ? $management['manager'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(): array
    {
        $management = $this->hut->management ?? [];

        return is_array($management['contract'] ?? null) ? $management['contract'] : [];
    }

    private function contractDate(string $key): ?CarbonImmutable
    {
        $value = self::text($this->contract()[$key] ?? null);

        if ($value === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
    }

    private function daysLeft(CarbonInterface $today): ?int
    {
        $end = $this->contractDate('end_date');

        return $end === null ? null : (int) CarbonImmutable::parse($today->format('Y-m-d'))->diffInDays($end, false);
    }

    /**
     * Link di un contatto social o web: un URL resta tale (mostrato senza schema), un nome utente diventa l'URL del
     * profilo.
     *
     * @return array{label: string, value: ?string, href: ?string}
     */
    private static function link(string $label, ?string $value, ?string $profileBase): array
    {
        if ($value === null) {
            return ['label' => $label, 'value' => null, 'href' => null];
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return ['label' => $label, 'value' => rtrim((string) preg_replace('#^https?://(www\.)?#i', '', $value), '/'), 'href' => $value];
        }

        if ($profileBase === null || str_contains($value, '.')) {
            return ['label' => $label, 'value' => preg_replace('#^www\.#i', '', $value), 'href' => 'https://'.$value];
        }

        return ['label' => $label, 'value' => $value, 'href' => $profileBase.ltrim($value, '@')];
    }

    private static function flag(mixed $value): ?int
    {
        return $value === 1 || $value === '1' ? 1 : ($value === 0 || $value === '0' ? 0 : null);
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private static function degrees(float $value): string
    {
        return str_replace('.', ',', number_format(abs($value), 5, '.', ''));
    }

    private static function shortDate(?CarbonImmutable $date): ?string
    {
        return $date?->format('d/m/Y');
    }

    private static function longDate(CarbonInterface $date): string
    {
        return $date->day.' '.self::MONTHS[$date->month - 1].' '.$date->year;
    }

    /** "23 maggio – 2 novembre 2026" (anno una volta sola se è lo stesso), `null` senza date. */
    private static function dateRange(mixed $start, mixed $end): ?string
    {
        $from = is_string($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) === 1 ? CarbonImmutable::createFromFormat('!Y-m-d', $start) : null;
        $to = is_string($end) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) === 1 ? CarbonImmutable::createFromFormat('!Y-m-d', $end) : null;
        $from = $from ?: null;
        $to = $to ?: null;

        if ($from === null && $to === null) {
            return null;
        }

        if ($from === null || $to === null) {
            return ($from === null ? 'fino al ' : 'dal ').self::longDate($from ?? $to);
        }

        $first = $from->year === $to->year ? $from->day.' '.self::MONTHS[$from->month - 1] : self::longDate($from);

        return $first.' – '.self::longDate($to);
    }
}
