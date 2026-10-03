<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

final readonly class ParsedManualBilancioFilename
{
    public function __construct(
        public string $codiceCai,
        public string $sectionLabel,
        public string $label,
        public string $extension,
    ) {}
}
