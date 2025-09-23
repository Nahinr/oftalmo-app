<?php

namespace App\Livewire\Clinic\Tabs;

use Livewire\Component;

class ImagesTab extends Component
{
    public int $patientId;

    public function mount(int $patientId): void
    {
        $this->patientId = $patientId;
    }

    public function render()
    {
        return view('livewire.clinic.tabs.images-tab');
    }
}
