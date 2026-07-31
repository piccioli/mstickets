<?php

declare(strict_types=1);

use App\Filament\Providers\AdminPanelProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ImportServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
    ImportServiceProvider::class,
];
