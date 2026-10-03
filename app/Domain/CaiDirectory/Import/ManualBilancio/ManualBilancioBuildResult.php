<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

final readonly class ManualBilancioBuildResult
{
    /**
     * @param  list<ManualBilancioRow>  $rows
     * @param  list<ManualBilancioAnomaly>  $anomalies
     */
    public function __construct(
        public array $rows,
        public array $anomalies,
    ) {}

    public function filesCount(): int
    {
        return count($this->rows);
    }

    public function sectionsCount(): int
    {
        return count(array_unique(array_map(static fn (ManualBilancioRow $r): string => $r->codiceCai, $this->rows)));
    }

    public function totalSize(): int
    {
        return array_sum(array_map(static fn (ManualBilancioRow $r): int => $r->size, $this->rows));
    }

    /**
     * @return array<string, int> codice anomalia → numero
     */
    public function anomalyCounts(): array
    {
        $counts = [];
        foreach ($this->anomalies as $anomaly) {
            $counts[$anomaly->code] = ($counts[$anomaly->code] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return list<ManualBilancioAnomaly>
     */
    public function anomaliesOf(string $code): array
    {
        return array_values(array_filter(
            $this->anomalies,
            static fn (ManualBilancioAnomaly $a): bool => $a->code === $code,
        ));
    }
}
