<?php

namespace App\Filament\Forms\Fields;

use App\Rules\DniYearInRange;
use App\Rules\UniqueDniDigits;
use App\Support\Dni;
use Filament\Forms\Components\TextInput;

class DniField
{
    public static function make(string $name = 'dni'): TextInput
    {
        return TextInput::make($name)
            ->label('DNI')
            ->mask('9999-9999-99999')
            ->placeholder('____-____-_____')
            ->minLength(15)
            ->maxLength(15)
            ->formatStateUsing(function ($state) {
                if (!$state) return null;
                $digits = Dni::onlyDigits($state);
                return strlen($digits) === 13 ? Dni::format13($digits) : $state;
            })
            ->dehydrateStateUsing(fn($state) => Dni::onlyDigits($state))
            ->rule('regex:/^\d{4}-\d{4}-\d{5}$/')
            ->rule(new DniYearInRange())
            ->rule(function () {
                $currentId = request()->route('record');
                return new UniqueDniDigits($currentId ? (int) $currentId : null);
            });
    }
}
