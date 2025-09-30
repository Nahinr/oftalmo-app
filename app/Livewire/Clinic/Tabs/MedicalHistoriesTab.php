<?php

namespace App\Livewire\Clinic\Tabs;

use Filament\Forms;
use App\Models\Patient;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MedicalHistory;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Attributes\On;
use App\Livewire\Traits\AuthorizesTab;



class MedicalHistoriesTab extends Component implements HasForms
{
    use InteractsWithForms, WithPagination, AuthorizesTab;

    public int $patientId;
    public ?MedicalHistory $editing = null;
    public bool $showForm = false;

    public ?array $data = [];

    protected function requiredPermission(): ?string
    {
        return 'history.view';
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function mount(int $patientId): void
    {
        $this->patientId = $patientId;
        $this->authorizeTab();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Consulta')
                ->schema([
                    DateTimePicker::make('visit_date')
                        ->label('Fecha de consulta')
                        ->default(now())
                        ->seconds(false)
                        ->native(false),

                    Grid::make([
                        'default' => 1,  // móvil: se apilan
                        'md'      => 6,  // md+: 6 columnas
                    ])->schema([
                        TextArea::make('findings')
                            ->label('Hallazgos')
                            ->rows(2)
                            ->columnSpan(['md' => 3]),

                        TextArea::make('tx')
                            ->label('Tratamiento')
                            ->rows(2)
                            ->columnSpan(['md' => 3]),
                    ]),

                    // Refracción en 3 columnas
                    Grid::make(3)->schema([
                        \Filament\Forms\Components\TextInput::make('refraction_od')
                            ->label('Refracción OD'),


                        \Filament\Forms\Components\TextInput::make('refraction_os')
                            ->label('Refracción OS'),

                        \Filament\Forms\Components\TextInput::make('refraction_add')
                            ->label('ADD')
                            ->suffix('D'),
                    ]),
                ]),
        ];
    }

    protected function getFormModel(): MedicalHistory|string|null
    {
        return $this->editing ?? MedicalHistory::class;
    }

    public function create(): void
    {
        $this->editing = null;
        $this->data = [];
        $this->form->fill(['visit_date' => now()]);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->editing = MedicalHistory::where('patient_id', $this->patientId)->findOrFail($id);
         $this->data = [];
        $this->form->fill($this->editing->toArray());
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['patient_id'] = $this->patientId;
        $data['user_id'] = auth()->id();

        if ($this->editing) {
            $this->editing->update($data);
            Notification::make()->title('Consulta actualizada')->success()->send();
        } else {
            $this->editing = MedicalHistory::create($data);
            Notification::make()->title('Consulta creada')->success()->send();
        }

        $this->reset(['showForm']);
    }

    public function delete(int $id): void
    {
        // Por ahora eliminación dura; luego cambiamos a "anular" con un campo status.
        MedicalHistory::where('patient_id', $this->patientId)->whereKey($id)->delete();
        Notification::make()->title('Consulta eliminada')->success()->send();
    }

    public function render()
    {
        $this->authorizeTab();
        $items = MedicalHistory::query()
            ->with('user')
            ->where('patient_id', $this->patientId)
            ->orderByDesc('visit_date')
            ->paginate(5);

        return view('livewire.clinic.tabs.medical-histories-tab', compact('items'));
    }

    #[On('open-create-consulta')]
    public function openCreateFromSticky(): void
    {
        $this->create(); // reutiliza tu método existente
    }
}
