<?php

namespace App\Filament\Pages\Clinic;

use App\Models\Patient;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;

class Expedientes extends Page
{
    protected static ?string $navigationLabel = 'Expedientes';
    protected static ?string $navigationGroup = 'Clínica';
    protected static ?string $navigationIcon  = 'heroicon-o-folder-open';
    protected static ?int $navigationSort     = 10;
    protected static string $view = 'filament.pages.clinic.expedientes';

    public ?int $patientId = null;
    public ?Patient $patient = null;
    
    public string $tab = 'antecedentes';

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }


    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getHeading(): string
    {
        return 'Expedientes';
    }


    public function getBreadcrumbs(): array
    {
        return [
            '#'                                     => 'Clínica',  
            url()->current()                        => 'Expedientes',
        ];
    }


    #[On('patient-selected')]
    public function loadPatient(int $id): void
    {
        $with = ['contacts'];
        if (method_exists(\App\Models\Patient::class, 'guardian')) $with[] = 'guardian';

        $this->patientId = $id;
        $this->patient   = \App\Models\Patient::with($with)->find($id);
        $this->tab = 'antecedentes';
    }

}
