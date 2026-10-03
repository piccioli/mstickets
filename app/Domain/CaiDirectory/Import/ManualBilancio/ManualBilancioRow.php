<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

final readonly class ManualBilancioRow
{
    public function __construct(
        public string $codiceCai,
        public string $regione,
        public int $anno,
        public string $tipo,
        public string $titolo,
        public string $fileName,
        public string $path,
        public string $mimeType,
        public int $size,
        public string $hashSha256,
    ) {}
}
