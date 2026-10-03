<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

use App\Domain\CaiDirectory\Enums\CaiDocumentType;

final readonly class ManualBilancioClassification
{
    public function __construct(
        public CaiDocumentType $type,
        public string $title,
        public int $year,
    ) {}
}
