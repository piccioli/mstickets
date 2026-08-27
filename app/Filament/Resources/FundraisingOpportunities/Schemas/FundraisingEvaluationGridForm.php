<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Schemas;

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Services\CalculateEvaluationTotals;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Griglia di valutazione (US-504, §6.6.2): un campo `scores.{criterion_key}` per ogni
 * {@see FundraisingEvaluationCriterion}, raggruppato per blocco. I criteri del blocco
 * "criteri_principali" hanno anche una nota testuale `notes.{criterion_key}`. Il totale
 * si ricalcola con {@see CalculateEvaluationTotals} (stesso service del salvataggio, US-503)
 * ad ogni cambio di un campo `->live()`, senza bisogno di un submit.
 */
class FundraisingEvaluationGridForm
{
    private const GROUP_LABELS = [
        'criteri_principali' => 'Criteri principali',
        'requisiti_base' => 'Requisiti base',
        'qualitativi' => 'Qualitativi',
        'premiali' => 'Premiali',
        'rischi' => 'Rischi',
    ];

    /**
     * @return array<int, Component>
     */
    public static function components(): array
    {
        return [
            self::totalsPlaceholder(),
            ...collect(self::GROUP_LABELS)
                ->map(fn (string $label, string $group): Section => self::groupSection($group, $label))
                ->values()
                ->all(),
        ];
    }

    private static function totalsPlaceholder(): Placeholder
    {
        return Placeholder::make('evaluation_totals')
            ->label('Totale valutazione')
            ->content(function (Get $get): string {
                $totals = CalculateEvaluationTotals::fromScores(self::currentScores($get));

                return sprintf(
                    'Positivo: %d · Negativo: %d · Totale: %d',
                    $totals['positive'],
                    $totals['negative'],
                    $totals['total'],
                );
            })
            ->columnSpanFull();
    }

    private static function groupSection(string $group, string $label): Section
    {
        $fields = collect(FundraisingEvaluationCriterion::cases())
            ->filter(fn (FundraisingEvaluationCriterion $criterion): bool => $criterion->group() === $group)
            ->flatMap(fn (FundraisingEvaluationCriterion $criterion): array => self::criterionFields($criterion, $group))
            ->all();

        return Section::make($label)
            ->columns(2)
            ->schema($fields);
    }

    /**
     * @return array<int, Component>
     */
    private static function criterionFields(FundraisingEvaluationCriterion $criterion, string $group): array
    {
        $fields = [
            TextInput::make("scores.{$criterion->value}")
                ->label($criterion->getLabel())
                ->numeric()
                ->minValue($criterion->min())
                ->maxValue($criterion->max())
                ->helperText(sprintf('Range: %d – %d', $criterion->min(), $criterion->max()))
                ->live(),
        ];

        if ($group === 'criteri_principali') {
            $fields[] = Textarea::make("notes.{$criterion->value}")
                ->label('Nota')
                ->rows(2);
        }

        return $fields;
    }

    /**
     * @return array<string, int>
     */
    private static function currentScores(Get $get): array
    {
        /** @var array<string, mixed> $rawScores */
        $rawScores = $get('scores') ?? [];

        return collect($rawScores)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }
}
