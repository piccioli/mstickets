<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Pages;

use App\Domain\Fundraising\Actions\SaveEvaluationScores;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Il tab "Valutazione" (US-504) salva su `fundraising_evaluation_scores`, non su una colonna
 * del model FundraisingOpportunity: `scores`/`notes` vengono estratti dai dati del form prima
 * di aggiornare il record e passati a {@see SaveEvaluationScores} (US-503), l'unico punto
 * d'ingresso per quel salvataggio. Richiede esplicitamente il permesso `fundraising.evaluate`
 * (distinto da `fundraising.update`, con cui si può aprire questa pagina): il tab lo nasconde
 * già in UI a chi non ce l'ha, questo controllo è la seconda barriera lato server.
 */
class EditFundraisingOpportunity extends EditRecord
{
    protected static string $resource = FundraisingOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        abort_unless($record instanceof FundraisingOpportunity, 404);

        $data['scores'] = $record->evaluationScores
            ->mapWithKeys(fn (FundraisingEvaluationScore $score): array => [$score->criterion_key->value => $score->score])
            ->all();

        $data['notes'] = $record->evaluationScores
            ->mapWithKeys(fn (FundraisingEvaluationScore $score): array => [$score->criterion_key->value => $score->notes])
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof FundraisingOpportunity, 404);

        /** @var array<string, mixed> $rawScores */
        $rawScores = $data['scores'] ?? [];

        /** @var array<string, string|null> $notes */
        $notes = $data['notes'] ?? [];

        unset($data['scores'], $data['notes']);

        $scores = collect($rawScores)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        $record->fill($data);
        $record->save();

        if ($scores !== []) {
            $user = Auth::user();

            abort_unless($user instanceof User, 403);
            abort_unless($user->can(Permission::FundraisingEvaluate), 403);

            $record = SaveEvaluationScores::run($record, $scores, $notes, $user);
        }

        return $record->fresh() ?? $record;
    }
}
