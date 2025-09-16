<?php

namespace App\Filament\Forms\Fields;

use App\Support\Dni;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Rules\DniYearInRange;
use App\Rules\UniqueDniDigits;
use Illuminate\Validation\Rule;
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
            ->rule(function (Get $get, ?Patient $record) {
                $currentId = $record?->getKey(); // Filament pasa el modelo actual en edición
                $digits = Dni::onlyDigits($get('dni')); // comparamos contra lo que se guardará
                return Rule::unique('patients', 'dni')
                    ->ignore($currentId)               // ignora el registro en edición
                    ->where(fn ($q) => $q->where('dni', $digits));
            });
    }
}
