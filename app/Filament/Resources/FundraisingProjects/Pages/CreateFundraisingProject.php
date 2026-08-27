<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingProjects\Pages;

use App\Filament\Resources\FundraisingProjects\FundraisingProjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateFundraisingProject extends CreateRecord
{
    protected static string $resource = FundraisingProjectResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = Auth::id();

        return static::getModel()::create($data);
    }
}
