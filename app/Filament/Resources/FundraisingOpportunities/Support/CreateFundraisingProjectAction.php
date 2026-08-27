<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Support;

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Fundraising\Policies\FundraisingProjectPolicy;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Header action "Crea progetto" (US-505, §6.6.1): crea un `FundraisingProject`
 * con `fundraising_opportunity_id` precompilato e `title` precompilato dal nome
 * dell'opportunità (editabile prima del salvataggio). Ritorna `null` (nessun
 * bottone) se l'utente non ha `fundraising.create` ({@see FundraisingProjectPolicy}).
 */
final class CreateFundraisingProjectAction
{
    public static function build(FundraisingOpportunity $opportunity): ?Action
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can(Permission::FundraisingCreate)) {
            return null;
        }

        return Action::make('create_project')
            ->label('Crea progetto')
            ->icon('heroicon-o-folder-plus')
            ->schema([
                TextInput::make('title')
                    ->label('Titolo progetto')
                    ->default($opportunity->name)
                    ->required(),
            ])
            ->action(function (array $data) use ($opportunity, $user): void {
                $project = FundraisingProject::create([
                    'title' => (string) $data['title'],
                    'fundraising_opportunity_id' => $opportunity->id,
                    'created_by' => $user->id,
                ]);

                Notification::make()
                    ->success()
                    ->title('Progetto creato')
                    ->body($project->title)
                    ->send();
            });
    }
}
