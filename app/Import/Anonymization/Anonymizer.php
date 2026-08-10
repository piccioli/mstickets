<?php

declare(strict_types=1);

namespace App\Import\Anonymization;

use Illuminate\Support\Facades\Hash;
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

    /**
     * Password fissa nota per ogni utente importato con `--anonymize`: mai
     * l'hash v1 reale fuori produzione (US-R01). Non è un seed derivato per
     * utente (a differenza di nome/email) — un solo hash noto, comunicato a
     * fine `make setup`/deploy, è sufficiente per il login di collaudo.
     */
    private const FIXED_PASSWORD = 'password';

    /**
     * @param  array<int, array{name: string, email: string}>  $referenceUsers  Mappa id v1 →
     *                                                                          nome+email fissi noti per gli utenti di riferimento del collaudo
     *                                                                          (`docs/collaudo/00-istruzioni-generali.md`): hanno sempre precedenza
     *                                                                          sull'algoritmo generico di {@see self::nameFor()}/{@see self::emailFor()}.
     *                                                                          Vuota di default: nessun id ha un trattamento speciale a meno di essere
     *                                                                          elencato qui. Il nome è un'etichetta di ruolo generica ("Amministratore
     *                                                                          Collaudo"), non il nome reale dell'utente v1 scelto per quell'id — coerente
     *                                                                          con lo scopo di `--anonymize`, anche nei documenti di collaudo.
     */
    public function __construct(
        private readonly string $testDomain,
        private readonly array $referenceUsers = [],
    ) {}

    /**
     * Dominio di test usato per le email fittizie generiche: il primo dell'allowlist già
     * condivisa col guard applicativo che blocca l'invio verso indirizzi reali
     * fuori produzione (`App\Support\Mail\BlockRealRecipientsOutsideProduction`),
     * così ogni email generata da `--anonymize` è per costruzione già permessa. Le email
     * di riferimento fisse (`reference_users`, dominio `oc.test`) sono incluse nella
     * stessa allowlist per lo stesso motivo.
     */
    public static function default(): self
    {
        /** @var array<int, string> $domains */
        $domains = config('orchestrator.anonymization.mail_test_domains', ['test.orchestrator.invalid']);

        /** @var array<int, array{name: string, email: string}> $referenceUsers */
        $referenceUsers = config('orchestrator.anonymization.reference_users', []);

        return new self($domains[0] ?? 'test.orchestrator.invalid', $referenceUsers);
    }

    /**
     * Un id elencato in `$referenceUsers` ottiene sempre il suo nome fisso noto, mai
     * quello generato dall'algoritmo generico — stesso principio di {@see self::emailFor()}.
     */
    public function nameFor(int|string $seed): string
    {
        $reference = $this->referenceUserFor($seed);

        if ($reference !== null) {
            return $reference['name'];
        }

        return sprintf('%s %s', $this->firstNameFor($seed), $this->lastNameFor($seed));
    }

    /**
     * Hash Laravel della password fissa nota ({@see self::FIXED_PASSWORD}),
     * mai l'hash v1 copiato as-is. Non deterministico byte-per-byte (bcrypt
     * sala casualmente ad ogni chiamata): per questo lo stage chiamante deve
     * trattarlo come insert-only, mai confrontarlo in un diff/update.
     */
    public function passwordHash(): string
    {
        return Hash::make(self::FIXED_PASSWORD);
    }

    /**
     * Un id elencato in `$referenceUsers` (utenti di riferimento del collaudo) ottiene
     * sempre la sua email fissa nota, mai quella generata dall'algoritmo generico —
     * verificato PRIMA di qualunque altra cosa, così un id di riferimento resta stabile
     * anche se la tabella nome/cognome cambiasse in futuro.
     *
     * Per tutti gli altri id, il seed è incluso letteralmente nella local-part per
     * garantire l'unicità (vincolo `unique` su `users.email`): la combinazione
     * nome/cognome da sola (30×30) potrebbe ripetersi ben prima di esaurire gli utenti
     * reali del dump.
     */
    public function emailFor(int|string $seed): string
    {
        $reference = $this->referenceUserFor($seed);

        if ($reference !== null) {
            return $reference['email'];
        }

        $local = Str::slug("{$this->firstNameFor($seed)}.{$this->lastNameFor($seed)}", '.');

        return sprintf('%s.%s@%s', $local, $seed, $this->testDomain);
    }

    /**
     * @return array{name: string, email: string}|null
     */
    private function referenceUserFor(int|string $seed): ?array
    {
        // `$seed` arriva da un id di riga DB: può essere int o stringa numerica a
        // seconda del driver/query builder — normalizzato a int solo per il lookup,
        // mai per la generazione generica (che usa il seed originale).
        if (! is_numeric($seed)) {
            return null;
        }

        return $this->referenceUsers[(int) $seed] ?? null;
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
