<?php

declare(strict_types=1);

use App\Import\Parsers\CustomerRequestParser;

test('no reply blocks: the whole content is a single original message', function (): void {
    $messages = CustomerRequestParser::parse('<p>Per favore controlla le scadenze dei contratti.</p>');

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->isOriginal)->toBeTrue()
        ->and($messages[0]->author)->toBeNull()
        ->and($messages[0]->postedAt)->toBeNull()
        ->and($messages[0]->body)->toBe('<p>Per favore controlla le scadenze dei contratti.</p>');
});

test('empty input produces no messages', function (): void {
    expect(CustomerRequestParser::parse(''))->toBe([]);
    expect(CustomerRequestParser::parse('   '))->toBe([]);
});

test('a real prepended reply chain (story id 1641 from the v1 dump) is decomposed in chronological order', function (): void {
    $html = "Riccardo Bernasconi ha risposto il: 21-01-2026 11:54\n <div style='background-color: #f8f9fa; border-left: 4px solid #6c757d; padding: 10px 20px;'> <p><p>Ciao Marco, allora si procede su due fronti.</p> </p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div>OTCO/SO CCEC ha risposto il: 20-01-2026 13:58\n <div style='background-color: #fff7e6; border-left: 4px solid #ffa940; padding: 10px 20px;'> <p><p>sulla piattaforma è stata di fatto azzerata la Commissione</p> </p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div><p>aggiornare l'OTCO CCEC in piattaforma </p>";

    $messages = CustomerRequestParser::parse($html);

    expect($messages)->toHaveCount(3);

    expect($messages[0]->isOriginal)->toBeTrue()
        ->and($messages[0]->author)->toBeNull()
        ->and($messages[0]->postedAt)->toBeNull()
        ->and($messages[0]->body)->toContain("aggiornare l'OTCO CCEC in piattaforma");

    expect($messages[1]->isOriginal)->toBeFalse()
        ->and($messages[1]->author)->toBe('OTCO/SO CCEC')
        ->and($messages[1]->postedAt?->toDateTimeString())->toBe('2026-01-20 13:58:00')
        ->and($messages[1]->body)->toContain('azzerata la Commissione');

    expect($messages[2]->isOriginal)->toBeFalse()
        ->and($messages[2]->author)->toBe('Riccardo Bernasconi')
        ->and($messages[2]->postedAt?->toDateTimeString())->toBe('2026-01-21 11:54:00')
        ->and($messages[2]->body)->toContain('Ciao Marco');
});

test('a chain of only reply blocks with no trailing original content produces no original message', function (): void {
    $html = "Autore ha risposto il: 01-02-2026 10:00\n <div style='background-color: #f8f9fa; border-left: 4px solid #6c757d; padding: 10px 20px;'> <p>Corpo</p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div>";

    $messages = CustomerRequestParser::parse($html);

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->isOriginal)->toBeFalse()
        ->and($messages[0]->author)->toBe('Autore');
});

test('a real Gmail forwarded-quote conversation (story id 3642) is not decomposed: single fallback block', function (): void {
    $html = '<p>Il giorno gio 4 giu 2026 alle ore 10:18 Editoria CAI &lt;editoria@cai.it&gt; ha scritto:</p><blockquote><p>Ciao Ivo, ti rispondo a nome di tutti.</p></blockquote><p>Grazie mille!</p>';

    $messages = CustomerRequestParser::parse($html);

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->isOriginal)->toBeTrue()
        ->and($messages[0]->body)->toBe($html);
});

test('an out-of-range calendar date in a reply block yields a null postedAt instead of silently rolling over', function (): void {
    $html = "Autore ha risposto il: 31-04-2026 10:00\n <div style='background-color: #f8f9fa; border-left: 4px solid #6c757d; padding: 10px 20px;'> <p>Corpo</p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div><p>Originale</p>";

    $messages = CustomerRequestParser::parse($html);

    expect($messages)->toHaveCount(2)
        ->and($messages[1]->author)->toBe('Autore')
        ->and($messages[1]->postedAt)->toBeNull();
});
