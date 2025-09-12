<?php

namespace App\Filament\Resources;

use Closure;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Patient;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Pages\Actions\EditAction;
use Filament\Pages\Actions\ViewAction;
use App\Filament\Forms\Fields\DniField;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Actions\DeleteAction;
use App\Filament\Forms\Fields\PhoneField;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\Pages\EditPatient;
use App\Filament\Resources\PatientResource\Pages\ListPatients;
use App\Filament\Resources\PatientResource\Pages\CreatePatient;
use Dom\Text;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Clínica';
    protected static ?string $navigationLabel = 'Pacientes';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Paciente';
    protected static ?string $pluralModelLabel = 'Pacientes';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema(self::formSchema());
    }
    
    public static function formSchema(): array
    {
        $formatAge = function (?string $date): ?string {
            if (!$date) return null;
            $birth = \Carbon\Carbon::parse($date);
            $now   = now();
            if ($birth->greaterThan($now)) return null;

            $diff   = $birth->diff($now);
            $years  = $diff->y;
            $months = $diff->m;
            $days   = $diff->d;

            $parts = [];
            if ($years > 0) {
                $parts[] = $years . ' ' . ($years === 1 ? 'año' : 'años');
            }
            if ($months > 0 || $years === 0) {
                $parts[] = $months . ' ' . ($months === 1 ? 'mes' : 'meses');
            }
            if ($years === 0 && $months === 0 && $days > 0) {
                $parts[] = $days . ' ' . ($days === 1 ? 'día' : 'días');
            }

            return trim(implode(' ', $parts));
        };
        return [
            Section::make('Datos del paciente')
                ->schema([
                    Grid::make(12)->schema([
                        TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(6),

                        TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(6),

                        // DNI con formato y validación personalizada
                        DniField::make()->columnSpan(6),
                        
                        ...PhoneField::schema(),

                        Select::make('sex')
                            ->label('Sexo')
                            ->options([
                                'M' => 'Masculino',
                                'F' => 'Femenino',
                            ])
                            ->native(false)
                            ->columnSpan(4),

                        DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->closeOnDateSelection()
                            ->rule('before_or_equal:today')
                            ->live()
                            ->maxDate(now())
                            ->placeholder('dd/mm/aaaa')
                            ->afterStateUpdated(function ($state, Set $set) use ($formatAge) {
                                $set('age', $formatAge($state));
                            })
                            ->afterStateHydrated(function ($state, Set $set) use ($formatAge) {
                                $set('age', $formatAge($state));
                            })
                            ->columnSpan(4),


                        TextInput::make('age')
                            ->label('Edad')
                            ->disabled()
                            ->dehydrated(false) // no se guarda en BD
                            ->columnSpan(4),

                        TextInput::make('occupation')
                            ->label('Ocupación')
                            ->maxLength(255)
                            ->columnSpan(6),

                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpan(6),
                    ]),
                ])->compact(),
        ];
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('full_name')
                    ->label('Paciente')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->wrap(),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->copyable()
                    ->badge(),

                TextColumn::make('sex')
                    ->label('Sexo')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'M' => 'Masculino',
                        'F' => 'Femenino',
                        default => '-',
                    })
                    ->toggleable(),

                TextColumn::make('birth_date')
                    ->label('Nacimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('age')
                    ->label('Edad')
                    ->getStateUsing(fn (Patient $record) => $record->birth_date ? Carbon::parse($record->birth_date)->age : null)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('occupation')
                    ->label('Ocupación')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
