<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Pages;

use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateFundraisingOpportunity extends CreateRecord
{
    protected static string $resource = FundraisingOpportunityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = Auth::id();

        return static::getModel()::create($data);
    }
}
