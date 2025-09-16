<?php

namespace App\Filament\Resources;

use Closure;
use Dom\Text;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\PatientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PatientResource\Pages\EditPatient;
use App\Filament\Resources\PatientResource\Pages\ListPatients;
use App\Filament\Resources\PatientResource\Pages\CreatePatient;
use Filament\Facades\Filament;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with('contacts');          // evita N+1
                
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

        $isMinor = function (?string $date): bool {
            if (!$date) return false;
            try {
                return \Carbon\Carbon::parse($date)->age < 18;
            } catch (\Throwable $e) {
                return false;
            }
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
                            ->dehydrated(false)
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

            // Toggle (solo adultos)
            \Filament\Forms\Components\Toggle::make('has_guardian')
                ->label('Tiene encargado/tutor')
                ->helperText('Actívalo si el adulto depende de un responsable')
                ->inline(false)
                ->default(false)
                ->dehydrated(false)
                ->live()
                ->visible(function (Get $get) use ($isMinor) {
                    $birth = $get('birth_date');
                    return $birth ? !$isMinor($birth) : false;
                }),

            // Bloque Encargado/Tutor
            Section::make('Encargado / Tutor')
                ->description('Datos del responsable del paciente ')
                ->schema([
                    \Filament\Forms\Components\Repeater::make('contacts')
                        ->relationship('contacts')
                        ->label('Lista de encargados')
                        ->minItems(function (Get $get) use ($isMinor) {
                                $birth  = $get('birth_date');
                                $toggle = (bool) $get('has_guardian');

                                // Si es obligatorio: exactamente 1
                                return ($birth && $isMinor($birth)) || $toggle ? 1 : 0;
                            })
                            ->maxItems(1) // 👈 nunca más de 1
                            ->defaultItems(function (Get $get) use ($isMinor) {
                                $birth  = $get('birth_date');
                                $toggle = (bool) $get('has_guardian');
                                return ($birth && $isMinor($birth)) || $toggle ? 1 : 0;
                            })
                        ->addActionLabel('Añadir encargado')
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

                                Select::make('relationship')
                                    ->label('Parentesco')
                                    ->options([
                                        'Mother' => 'Madre',
                                        'Father' => 'Padre',
                                        'Guardian' => 'Tutor/Encargado',
                                        'Spouse' => 'Cónyuge',
                                        'Relative' => 'Familiar',
                                        'Other' => 'Otro',
                                    ])
                                    ->native(false)
                                    ->columnSpan(4),

                                // Dentro del Grid del Repeater (en la sección Encargado/Tutor):
                                ... \App\Filament\Forms\Fields\PhoneField::schema(
                                    countryField: 'guardian_phone_country',   // efímero, NO BD
                                    nationalField: 'guardian_phone_national', // efímero, NO BD
                                    e164Field: 'phone',                       // este SÍ es el de BD (contacts.phone)
                                    countrySpan: 2,
                                    numberSpan: 6
                                ),

                                \Filament\Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->rows(2)
                                    ->columnSpan(12),
                            ]),
                        ])
                        ->columns(1)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            trim(($state['first_name'] ?? '') . ' ' . ($state['last_name'] ?? '')) ?: 'Encargado'
                        )
                        ->visible(function (Get $get) use ($isMinor) {
                            $birth = $get('birth_date');
                            $toggle = (bool) $get('has_guardian');
                            return ($birth && $isMinor($birth)) || $toggle;
                        })
                        ->minItems(function (Get $get) use ($isMinor) {
                            $birth = $get('birth_date');
                            $toggle = (bool) $get('has_guardian');
                            return ($birth && $isMinor($birth)) || $toggle ? 1 : 0;
                        })
                        ->defaultItems(function (Get $get) use ($isMinor) {
                            $birth = $get('birth_date');
                            $toggle = (bool) $get('has_guardian');
                            return ($birth && $isMinor($birth)) || $toggle ? 1 : 0;
                        }),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->visible(function (Get $get) use ($isMinor) {
                    $birth = $get('birth_date');
                    $toggle = (bool) $get('has_guardian');
                    return ($birth && $isMinor($birth)) || $toggle;
                }),
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
                    ->wrap(),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->copyable()
                    ->badge(),

                TextColumn::make('sex')
                    ->label('Sexo')
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
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make('contact_phone')
                    ->label('Teléfono contacto')
                    ->getStateUsing(fn (Patient $r) => optional($r->contacts->first())->phone)
                    ->badge()
                    ->color('info')
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
                Tables\Filters\TrashedFilter::make()->label('Archivados'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => Filament::auth()->user()?->can('patient.view')),
                Tables\Actions\EditAction::make()->visible(fn () => Filament::auth()->user()?->can('patient.update')),
                Tables\Actions\DeleteAction::make()->label('Archivar')->visible(fn () => Filament::auth()->user()?->can('patient.delete'))->requiresConfirmation(),
                Tables\Actions\RestoreAction::make()->label('Restaurar')->visible(fn () => Filament::auth()->user()?->can('patient.restore')),
                Tables\Actions\ForceDeleteAction::make()->label('Eliminar definitivamente')->visible(fn () => Filament::auth()->user()?->can('patient.forceDelete')),
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
