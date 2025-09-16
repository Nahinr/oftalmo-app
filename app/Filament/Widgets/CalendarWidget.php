<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function fetchEvents(array $fetchInfo): array
    {
        // Pronto cargaremos desde BD. Por ahora, vacío.
        return [];
    }

    public function config(): array
    {
        return [
            'initialView'   => 'timeGridWeek',
            'slotDuration'  => '00:30:00',
            'selectable'   => true,
            'headerToolbar' => [
                'left'   => 'dayGridMonth,timeGridWeek,timeGridDay',
                'center' => 'title',
                'right'  => 'today prev,next',
            ],
        ];
    }
}
