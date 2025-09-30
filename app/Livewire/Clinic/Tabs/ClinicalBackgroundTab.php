<?php

namespace App\Livewire\Clinic\Tabs;

use Filament\Forms;
use App\Models\Patient;
use Livewire\Component;
use App\Models\ClinicalBackground;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use App\Livewire\Traits\AuthorizesTab;

class ClinicalBackgroundTab extends Component implements HasForms
{
    use InteractsWithForms, AuthorizesTab;

    public int $patientId;
    public ?ClinicalBackground $record = null;

    protected function requiredPermission(): ?string
    {
        return 'clinical-background.view';
    }


    public ?array $data = [];

    protected function getFormStatePath(): string
    {
        return 'data';
    }


    public function mount(int $patientId): void
    {
        $this->patientId = $patientId;
        $this->authorizeTab();
        $this->record = ClinicalBackground::query()
            ->with('user')
            ->firstWhere('patient_id', $patientId);

        $this->data = [];
        if ($this->record) {
            $this->form->fill($this->record->toArray());
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Antecedentes clínicos del paciente')
                ->description(function () {
                    if (! $this->record) {
                        return 'Aún no registrado.';
                    }

                    // Usuario
                    $user = $this->record->user?->name
                        ? trim(($this->record->user->name ?? '') . ' ' . ($this->record->user->last_name ?? ''))
                        : '—';

                    // Fecha solo con día en español
                    $tz    = config('app.timezone', 'America/Tegucigalpa');
                    $fecha = optional($this->record->created_at)
                        ?->timezone($tz)
                        ->locale('es')
                        ->translatedFormat('l, j \\d\\e F, Y');
                    if ($fecha) {
                        $fecha = \Illuminate\Support\Str::ucfirst($fecha);
                    }

                    // Relativo: "hace 1 año y 2 meses" -> queremos solo "1 año y 2 meses"
                    $rel = optional($this->record->created_at)
                        ?->timezone($tz)
                        ->locale('es')
                        ->diffForHumans([
                            'parts' => 2,     // máx 2 partes
                            'join'  => true,  // usa "y"
                            'short' => false,
                        ]);

                    $relSolo = $rel ? \Illuminate\Support\Str::replaceFirst('hace ', '', $rel) : null;

                    // Devolvemos HTML para forzar la segunda línea
                    return new HtmlString(
                        "Creado por: {$user} · {$fecha}" .
                        ($relSolo
                            ? "<div class='mt-0.5 text-gray-500 text-sm'>Creado hace: {$relSolo}</div>"
                            : ''
                        )
                    );
                })
                ->schema([
                    Textarea::make('clinical_history')->label('Historia clínica')
                    ->rows(2)
                    ->placeholder('Motivo de consulta, síntomas (duración, intensidad), evolución, tratamientos previos…')
                    ->columnSpanFull(),
                ]),
                Grid::make(['default' => 1, 'md' => 2,
                ])
                ->schema([
                    // Columna 1
                    Section::make('Historia Médica / Quirúrgica / Traumática')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('ocular_meds')->label('Medicinas oculares')->placeholder('N/A'),
                                TextInput::make('systemic_meds')->label('Medicaciones sistémicas')->placeholder('N/A'),
                                TextInput::make('allergies')->label('Alergias')->placeholder('N/A'),
                                TextInput::make('personal_path_history')->label('A.P.P.')->placeholder('N/A'),
                                TextInput::make('trauma_surgical_history')->label('A.T.Q.')->placeholder('N/A'),
                            ]),
                        ])
                        ->columnSpan(1), // ← ocupa 1 de las 2 columnas

                    // Columna 2
                    Card::make('Antecedentes familiares')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('fam_glaucoma')->label('Glaucoma')->placeholder('N/A'),
                                TextInput::make('fam_cataract')->label('Desprendimiento de retina')->placeholder('N/A'),
                                TextInput::make('fam_blindness')->label('Cataratas')->placeholder('N/A'),
                                TextInput::make('fam_retinal_detachment')->label('Ceguera')->placeholder('N/A'),
                                TextInput::make('fam_diabetes')->label('Diabetes')->placeholder('N/A'),
                                TextInput::make('fam_hypertension')->label('Hipertensión')->placeholder('N/A'),
                                TextInput::make('fam_thyroid')->label('Tiroides')->placeholder('N/A'),
                                TextInput::make('fam_anemia')->label('Anemia')->placeholder('N/A'),
                                TextInput::make('fam_other')->label('Otros')->placeholder('N/A'),
                            ]),
                        ])
                        ->columnSpan(1), // ← ocupa 1 de las 2 columnas
                ])
                ->columnSpanFull(),

                Section::make('Agudeza visual / Lentes')
                    ->schema([
                        Grid::make(['default' => 1,'md' => 3, 
                        ])->schema([
                           Section::make('Agudeza visual CC')
                                ->schema([
                                        TextInput::make('av_cc_od')->label('OD')->maxLength(15)->placeholder('N/A'),
                                        TextInput::make('av_cc_os')->label('OS')->maxLength(15)->placeholder('N/A'),
                                ])
                                ->columnSpan(1),

                            // Columna 2: AV (SC)
                            Section::make('Agudeza visual SC')
                                ->schema([
                                        TextInput::make('av_sc_od')->label('OD')->maxLength(15)->placeholder('N/A'),
                                        TextInput::make('av_sc_os')->label('OS')->maxLength(15)->placeholder('N/A'),
                                ])
                                ->columnSpan(1),

                            // Columna 3: Receta de lentes
                            Section::make('Receta de lentes')
                                ->schema([
                                    // Si prefieres inputs cortos: TextInput; si pones recetas completas: Textarea
                                    TextInput::make('rx_od')->label('RX OD')->placeholder('N/A'),
                                    TextInput::make('rx_os')->label('RX OS')->placeholder('N/A'),
                                    TextInput::make('rx_add')->label('ADD')->placeholder('N/A')->suffix('D'),
                                ])
                                ->columnSpan(1),
                        ]),
                    ])
                    ->columnSpanFull(),


                Section::make('Lensometría / AV Extra / Cicloplejía')
                    ->schema([
                        // 1 columna en móvil, 3 columnas desde md+
                        Grid::make([
                            'default' => 1,
                            'md'      => 3,
                        ])->schema([

                            // Columna 1: Lensometría
                            Section::make('Lensometría')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('lensometry_od')
                                            ->label('OD')
                                            ->maxLength(60)
                                            ->placeholder('N/A'),
                                        TextInput::make('lensometry_os')
                                            ->label('OS')
                                            ->maxLength(60)
                                            ->placeholder('N/A'),
                                    ]),
                                ])
                                ->columnSpan(1),

                            // Columna 2: AV Extra
                            Section::make('AV CC')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('av_extra_od')
                                            ->label('OD')
                                            ->maxLength(15)
                                            ->placeholder('N/A'),
                                        TextInput::make('av_extra_os') 
                                            ->label('OS')
                                            ->maxLength(15)
                                            ->placeholder('N/A'),
                                    ]),
                                ])
                                ->columnSpan(1),

                            // Columna 3: Cicloplejía (ADD)
                            Section::make('ADD')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('add_cyclo_od')
                                            ->label('OD')
                                            ->maxLength(15)
                                            ->placeholder('N/A'),
                                        TextInput::make('add_cyclo_os')
                                            ->label('OS')
                                            ->maxLength(15)
                                            ->placeholder('N/A'),
                                    ]),
                                ])
                                ->columnSpan(1),
                        ]),
                    ])
                    ->columnSpanFull(),


                 Section::make('Examen Externo')
                ->schema([
                    // Párpados
                    Section::make('Párpados')->schema([
                        Textarea::make('eyelids_od')
                            ->label('OD')->autosize()
                            ->columnSpanFull(),
                        Textarea::make('eyelids_os')
                            ->label('OS')->autosize()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                    // Córnea
                    Section::make('Córnea')->schema([
                        Textarea::make('bio_cornea_od')
                            ->label('OD')->autosize()
                            ->columnSpanFull(),
                        Textarea::make('bio_cornea_os')
                            ->label('OS')->autosize()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                    // Cámara anterior (C/A)
                    Section::make('Cámara anterior (C/A)')->schema([
                        Textarea::make('bio_ca_od')
                            ->label('OD')->autosize()
                            ->columnSpanFull(),
                        Textarea::make('bio_ca_os')
                            ->label('OS')->autosize()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                    // Iris
                    Section::make('Iris')->schema([
                        Textarea::make('bio_iris_od')
                            ->label('OD')->autosize()
                            ->columnSpanFull(),
                        Textarea::make('bio_iris_os')
                            ->label('OS')->autosize()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                    // Cristalino
                    Section::make('Cristalino')->schema([
                        Textarea::make('bio_lens_od')
                            ->label('OD')->autosize()
                            ->columnSpanFull(),
                        Textarea::make('bio_lens_os')
                            ->label('OS')->autosize()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                    // Vítreo
                    Section::make('Vítreo')->schema([
                        Textarea::make('bio_vitreous_od')
                            ->label('OD')->autosize()
                            ->columnSpanFull(),
                        Textarea::make('bio_vitreous_os')
                            ->label('OS')->autosize()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
                ])
                ->columnSpanFull(),

                    Section::make('Tensión ocular / Fondo')
                        ->schema([
                            // 1 col en móvil, 2 cols desde md+
                            Grid::make([
                                'default' => 1,
                                'md'      => 2,
                            ])->schema([

                                // Columna 1 — Tensión ocular (AP)
                                Section::make('Tensión ocular (AP)')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('iop_ap_od')
                                                ->label('OD')
                                                ->maxLength(4)
                                                ->placeholder('N/A')
                                                ->suffix('mmHg')
                                                ->numeric()
                                                ->rule('decimal:0,1'),
                                            TextInput::make('iop_ap_os')
                                                ->label('OS')
                                                ->maxLength(4)
                                                ->placeholder('N/A')
                                                ->suffix('mmHg')
                                                ->numeric()
                                                ->rule('decimal:0,1'),
                                        ]),
                                    ])
                                    ->columnSpan(1),

                                // Columna 2 — Fondo ocular
                                Section::make('Fondo ocular')
                                    ->schema([
                                        Grid::make(2)->schema([
                                        TextInput::make('fundus_od')
                                            ->label('OD')
                                            ->placeholder('N/A'),
                                        TextInput::make('fundus_os')
                                            ->label('OS')
                                            ->placeholder('N/A'),
                                        ])
                                    ])
                                    ->columnSpan(1),
                            ]),
                        ])
                        ->columnSpanFull(),

                Section::make('Conclusiones y Plan de Tratamiento')
                ->schema([
                    // Conclusiones                    
                    TextInput::make('clinical_impression')->label('Impresión clínica'),
                    TextInput::make('special_tests')->label('Pruebas especiales'),
                    Textarea::make('disposition_and_treatment')->label('Disposición y tratamiento')->autosize(),
                ]),
        ];
    }

    protected function getFormModel(): ClinicalBackground|string|null
    {
        return $this->record ?? ClinicalBackground::class;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['patient_id'] = $this->patientId;

        if ($this->record) {

            if (empty($this->record->user_id)) {
            $data['user_id'] = auth()->id();
            }

            $this->record->update($data);
             $this->record->refresh()->load('user');
            Notification::make()->title('Antecedentes actualizados')->success()->send();
        } else {
            $data['user_id'] = auth()->id();
            $this->record = ClinicalBackground::create($data);
            $this->record->load('user');
            Notification::make()->title('Antecedentes creados')->success()->send();
        }
    }

    #[On('save-clinical-background')]
    public function saveFromStickyBar(): void
    {
        $this->save();
    }

    public function render() {
        $this->authorizeTab();
         return view('livewire.clinic.tabs.clinical-background-tab'); 
        }
}
