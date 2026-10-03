<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

final readonly class CampagnaSezioniRow
{
    /**
     * @param  list<string>  $links
     */
    public function __construct(
        public string $codiceCai,
        public string $name,
        public string $region,
        public ?string $statoBilanci,
        public array $links,
    ) {}
}
