<?php

declare(strict_types=1);

namespace App\Import\Anonymization;

use Illuminate\Support\Str;

/**
 * Genera identità/contenuti fittizi deterministici per `--anonymize` (§11.8 del
 * PRD): stesso seed (l'id v1 della riga) → stesso output a ogni esecuzione,
 * senza mutare lo stato globale del PRNG di PHP (niente `mt_srand`, solo un
 * hash puro per seed) — così un secondo stage/una seconda esecuzione nello
 * stesso processo non altera l'output già generato per un seed precedente.
 */
final class Anonymizer
{
    /** @var array<int, string> */
    private const FIRST_NAMES = [
        'Alessio', 'Bianca', 'Carlo', 'Debora', 'Enrico', 'Federica', 'Giulio', 'Ilaria',
        'Lorenzo', 'Martina', 'Nicola', 'Ombretta', 'Paolo', 'Rebecca', 'Simone', 'Tiziana',
        'Ugo', 'Valentina', 'Walter', 'Zoe', 'Andrea', 'Beatrice', 'Cesare', 'Diana',
        'Emanuele', 'Francesca', 'Gabriele', 'Helena', 'Ivo', 'Jessica',
    ];

    /** @var array<int, string> */
    private const LAST_NAMES = [
        'Aldi', 'Bruni', 'Conti', 'De Luca', 'Esposito', 'Ferri', 'Galli', 'Hoxha',
        'Ianni', 'Longo', 'Marino', 'Neri', 'Orlandi', 'Pace', 'Quaranta', 'Ricci',
        'Sartori', 'Testa', 'Uberti', 'Villa', 'Zanetti', 'Amato', 'Bianchi', 'Colombo',
        'Duranti', 'Ferrara', 'Gatti', 'Iori', 'Leone', 'Moretti',
    ];

    /** @var array<int, string> */
    private const BODY_WORDS = [
        'lorem', 'ipsum', 'ticket', 'richiesta', 'verifica', 'configurazione', 'ambiente',
        'aggiornamento', 'documento', 'funzionalità', 'segnalazione', 'esito', 'conferma',
        'analisi', 'sistema', 'modulo', 'accesso', 'errore', 'test', 'rilascio', 'contatto',
        'supporto', 'dettaglio', 'nota', 'allegato', 'utente', 'attività', 'progetto',
        'scadenza', 'priorità',
    ];

    public function __construct(private readonly string $testDomain) {}

    /**
     * Dominio di test usato per le email fittizie: il primo dell'allowlist già
     * condivisa col guard applicativo che blocca l'invio verso indirizzi reali
     * fuori produzione (`App\Support\Mail\BlockRealRecipientsOutsideProduction`),
     * così ogni email generata da `--anonymize` è per costruzione già permessa.
     */
    public static function default(): self
    {
        /** @var array<int, string> $domains */
        $domains = config('orchestrator.anonymization.mail_test_domains', ['test.orchestrator.invalid']);

        return new self($domains[0] ?? 'test.orchestrator.invalid');
    }

    public function nameFor(int|string $seed): string
    {
        return sprintf('%s %s', $this->firstNameFor($seed), $this->lastNameFor($seed));
    }

    /**
     * Il seed è incluso letteralmente nella local-part per garantire l'unicità
     * (vincolo `unique` su `users.email`): la combinazione nome/cognome da sola
     * (30×30) potrebbe ripetersi ben prima di esaurire gli utenti reali del dump.
     */
    public function emailFor(int|string $seed): string
    {
        $local = Str::slug("{$this->firstNameFor($seed)}.{$this->lastNameFor($seed)}", '.');

        return sprintf('%s.%s@%s', $local, $seed, $this->testDomain);
    }

    /**
     * Testo fittizio deterministico, con una lunghezza approssimativamente
     * proporzionale all'originale (§11.8: "mantenendo ... la distribuzione") —
     * non un identico numero di parole, solo lo stesso ordine di grandezza.
     */
    public function bodyFor(int|string $seed, int $originalLength = 0): string
    {
        $wordCount = max(6, (int) round($originalLength / 6));

        $words = [];

        for ($position = 0; $position < $wordCount; $position++) {
            $words[] = self::BODY_WORDS[$this->indexFor($seed, "body-{$position}", count(self::BODY_WORDS))];
        }

        return ucfirst(implode(' ', $words)).'.';
    }

    private function firstNameFor(int|string $seed): string
    {
        return self::FIRST_NAMES[$this->indexFor($seed, 'first-name', count(self::FIRST_NAMES))];
    }

    private function lastNameFor(int|string $seed): string
    {
        return self::LAST_NAMES[$this->indexFor($seed, 'last-name', count(self::LAST_NAMES))];
    }

    private function indexFor(int|string $seed, string $salt, int $modulo): int
    {
        return crc32("{$salt}:{$seed}") % $modulo;
    }
}
