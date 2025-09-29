<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\Component;
use Livewire\WithPagination;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Forms\Components\RichEditor;

use App\Models\Prescription;

class PrescriptionsTab extends Component implements HasForms
{
    use InteractsWithForms, WithPagination;

    public int $patientId;
    public ?Prescription $editing = null;
    public bool $showForm = false;

    public ?array $data = [];

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function mount(int $patientId,): void
    {
        $this->patientId = $patientId;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Receta')
                ->schema([
                    DateTimePicker::make('issued_at')
                        ->label('Fecha de emisión')
                        ->seconds(false)
                        ->default(now())               // usa tz del app
                        ->native(false)
                        ->required(),

                    Grid::make([
                        'default' => 1,
                        'md'      => 2,
                    ])->schema([
                        RichEditor::make('diagnosis')
                            ->label('Diagnóstico')
                            ->columnSpan(1)
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList',
                                'link',
                                'undo', 'redo',
                            ])
                            ->required(),

                        RichEditor::make('medications_description')
                            ->label('Medicamentos e indicaciones')
                            ->columnSpan(1)
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'bulletList', 'orderedList',
                                'link',
                                'undo', 'redo',
                            ])
                            ->required(),
                    ]),
                ]),
        ];
    }

    protected function getFormModel(): Prescription|string|null
    {
        return $this->editing ?? Prescription::class;
    }

    public function create(): void
    {
        $this->editing = null;
        $this->data = [];
        $this->form->fill([
            'issued_at' => now(),
        ]);
        $this->showForm = true;

        // si estás paginado, evita quedarte en páginas vacías
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->editing = Prescription::where('patient_id', $this->patientId)->findOrFail($id);
        $this->data = [];
        $this->form->fill($this->editing->toArray());
        $this->showForm = true;
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Validaciones clave (además de required en schema)
        $this->validate([
            'data.diagnosis'               => 'required|string|max:2000',
            'data.medications_description' => 'required|string|max:5000',
            'data.issued_at'               => 'required|date',
        ], [], [
            'data.diagnosis'               => 'diagnóstico',
            'data.medications_description' => 'medicamentos e indicaciones',
            'data.issued_at'               => 'fecha de emisión',
        ]);

        $state['patient_id'] = $this->patientId; // OBLIGATORIO
        $state['user_id']    = auth()->id();     // médico que crea/edita


        if ($this->editing) {
            $this->editing->update($state);
            Notification::make()->title('Receta actualizada')->success()->send();
        } else {
            $this->editing = Prescription::create($state);
            Notification::make()->title('Receta creada')->success()->send();
        }

        $this->reset(['showForm']);
    }

    public function delete(int $id): void
    {
        Prescription::where('patient_id', $this->patientId)->whereKey($id)->delete();
        Notification::make()->title('Receta eliminada')->success()->send();
    }

    public function render()
    {
        $items = Prescription::query()
            ->with(['user', 'patient'])
            ->where('patient_id', $this->patientId)
            ->orderByDesc('issued_at')
            ->paginate(5);

        return view('livewire.clinic.tabs.prescriptions-tab', compact('items'));
    }

    // Abrir el form desde la barra sticky
    #[On('open-create-prescription')]
    public function openCreateFromSticky(): void
    {
        $this->create();
    }
}
