<?php

declare(strict_types=1);

namespace Tests\Feature\Import\Fixtures;

use App\Import\Stages\Contracts\ImportStage;
use App\Import\Stages\ImportContext;
use App\Import\Stages\StageResult;
use Closure;

/**
 * Stage fittizio usato SOLO dai test del runner (US-201): nessuno stage reale
 * esiste ancora in questa fase (arrivano dalle story successive, US-202+).
 * Non registrato in config('import.stages'), vive solo sotto tests/.
 */
final class FakeImportStage implements ImportStage
{
    /**
     * @param  array<int, string>  $dependencies
     */
    public function __construct(
        private readonly string $name,
        private readonly array $dependencies = [],
        private readonly ?Closure $onRun = null,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function dependencies(): array
    {
        return $this->dependencies;
    }

    public function run(ImportContext $context): StageResult
    {
        if ($this->onRun !== null) {
            return ($this->onRun)($context);
        }

        return new StageResult(read: 1, created: $context->isDryRun() ? 0 : 1);
    }
}
