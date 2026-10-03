<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentType;
use App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioFilenameParser;
use App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioTypeMapper;

test('ManualBilancioFilenameParser parses a normalized file name', function (): void {
    $parsed = ManualBilancioFilenameParser::parse('9226005 - CAI Carrara - Mod D - Rendiconto per cassa.pdf');

    expect($parsed)->not->toBeNull()
        ->and($parsed->codiceCai)->toBe('9226005')
        ->and($parsed->sectionLabel)->toBe('CAI Carrara')
        ->and($parsed->label)->toBe('Mod D - Rendiconto per cassa')
        ->and($parsed->extension)->toBe('pdf');
});

test('ManualBilancioFilenameParser returns null for non conforming names', function (string $name): void {
    expect(ManualBilancioFilenameParser::parse($name))->toBeNull();
})->with(['.DS_Store', 'bilancio.pdf', '922600 - CAI X - Etichetta.pdf', '9226005 - CAI Carrara.pdf']);

test('ManualBilancioTypeMapper maps labels to type, title and year', function (string $label, CaiDocumentType $type, int $year): void {
    $c = ManualBilancioTypeMapper::map($label);

    expect($c->type)->toBe($type)->and($c->title)->toBe($label)->and($c->year)->toBe($year);
})->with([
    ['Mod A - Stato Patrimoniale', CaiDocumentType::ModA, 2025],
    ['Mod B - Rendiconto gestionale', CaiDocumentType::ModB, 2025],
    ['Mod D - Rendiconto per cassa', CaiDocumentType::ModD, 2025],
    ['Mod A e B - Stato Patrimoniale e Rendiconto gestionale', CaiDocumentType::ModAB, 2025],
    ['Bilancio completo - Mod A, Mod B e Relazione di missione', CaiDocumentType::CompletoABC, 2025],
    ['Bilancio completo - Mod A  Mod B e Relazione di missione', CaiDocumentType::CompletoABC, 2025],
    ['Bilancio completo - Mod D e Relazione revisori', CaiDocumentType::CompletoDRevisori, 2025],
    ['Relazione attività', CaiDocumentType::RelazioneAttivita, 2025],
    ['Relazione di missione', CaiDocumentType::RelazioneMissione, 2025],
    ['Relazione revisori', CaiDocumentType::RelazioneRevisori, 2025],
    ['Verbale assemblea', CaiDocumentType::VerbaleAssemblea, 2025],
    ['Bilancio sociale', CaiDocumentType::BilancioSociale, 2025],
    ['Relazione al bilancio', CaiDocumentType::RelazioneBilancio, 2025],
    ['Bilancio analitico contabile', CaiDocumentType::BilancioAnalitico, 2025],
    ['Bilancio riclassificato', CaiDocumentType::BilancioRiclassificato, 2025],
    ['Bilancio economico e finanziario', CaiDocumentType::BilancioEconomicoFinanziario, 2025],
    ['Bilancio consuntivo', CaiDocumentType::Altro, 2025],
    ['Conto economico', CaiDocumentType::Altro, 2025],
    ['Rendiconto per cassa', CaiDocumentType::Altro, 2025],
    ['Stato Patrimoniale e conto economico', CaiDocumentType::Altro, 2025],
    ['Nota integrativa', CaiDocumentType::Altro, 2025],
    ['Quote associative 2025', CaiDocumentType::Altro, 2025],
    ['Quote associative 2026', CaiDocumentType::Altro, 2026],
    ['Verbale revisori', CaiDocumentType::Altro, 2025],
    ['Bilancio consuntivo (2)', CaiDocumentType::Altro, 2025],
]);
