<x-filament-panels::page>
    @livewire(\App\Filament\Widgets\CalendarWidget::class)

    <style>
        /* Altura de cada franja de 30 min */
        .fc .fc-timegrid-slot {
            height: 2.6em; /* ajusta a tu gusto: 2.2em, 2.6em, 3em, etc. */
        }
        /* Ajuste visual del label (am/pm) y alineación */
        .fc .fc-timegrid-slot-label {
            padding-top: 6px;
            font-variant-numeric: tabular-nums;
        }
    </style>
</x-filament-panels::page>

