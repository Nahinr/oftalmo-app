<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Calendario extends Page
{
    protected static ?string $navigationGroup = 'Agenda';
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Calendario';
    protected static ?string $title           = 'Calendario';

    

    protected static string $view = 'filament.pages.calendario';
}
