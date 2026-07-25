<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case Admin = 'admin';
    case Developer = 'developer';
    case Manager = 'manager';
    case Customer = 'customer';
    case Fundraising = 'fundraising';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Amministratore',
            self::Developer => 'Sviluppatore',
            self::Manager => 'Manager',
            self::Customer => 'Cliente',
            self::Fundraising => 'Fundraising',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Developer => 'info',
            self::Manager => 'warning',
            self::Customer => 'success',
            self::Fundraising => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Admin => 'heroicon-o-shield-check',
            self::Developer => 'heroicon-o-code-bracket',
            self::Manager => 'heroicon-o-briefcase',
            self::Customer => 'heroicon-o-user',
            self::Fundraising => 'heroicon-o-currency-euro',
        };
    }
}
