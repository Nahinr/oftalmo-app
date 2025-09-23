<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="text-base font-semibold">Consultas</h3>
    </div>

    @if($showForm)
    <form wire:submit.prevent="save" class="space-y-4">
        {{ $this->form }}
        <div class="flex gap-2">
            <x-filament::button type="submit">Guardar</x-filament::button>
            <x-filament::button color="gray" wire:click="$set('showForm', false)" type="button">Cancelar
            </x-filament::button>
        </div>
    </form>
    <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
    @endif

    <div class="space-y-2">
        @forelse($items as $mh)
        <x-filament::section>
            <div class="flex items-start justify-between">
                <div>
                    @php
                        $tz = config('app.timezone', 'America/Tegucigalpa');

                        $dt = optional($mh->visit_date)?->timezone($tz);

                        // Fecha y hora
                        $fecha = $dt?->locale('es')->translatedFormat('l, j \\d\\e F, Y');
                        if ($fecha) { $fecha = \Illuminate\Support\Str::ucfirst($fecha); }
                        $hora = $dt?->format('g:i a');

                        // Relativo desde creación -> "hace 1 año y 2 meses" / "hace 5 días"
                        $creado = optional($mh->created_at)?->timezone($tz);
                        // 👇 OJO: sin pasar now() como segundo parámetro, para evitar "antes"
                        $rel = $creado?->locale('es')->diffForHumans([
                            'parts' => 2,
                            'join'  => true,
                            'short' => false,
                        ]);

                        // Quitar prefijo "hace " y variantes si quieres dejar solo "1 año y 2 meses"
                        $relCorto = $rel ? \Illuminate\Support\Str::replaceFirst('hace ', '', $rel) : null;
                    @endphp

                    <div class="font-medium">
                        {{-- Línea 1: Fecha y hora en la misma fila --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span><span class="font-semibold">Fecha de creación: </span> <span class="text-gray-500">{{ $fecha ?? '—' }}</span></span>
                            <span class="text-slate-400">•</span>
                            <span><span class="font-semibold">Hora: </span><span class="text-gray-500">{{ $hora ?? '—' }}</span></span>
                        </div>

                        {{-- Línea 2: Creado hace … (debajo) --}}
                        @if ($relCorto)
                            <div class="mt-1  mb-1">
                                <span class="font-semibold">Creado hace:</span> <span class="text-gray-500">{{ $relCorto }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <span class="font-semibold">Médico tratante: </span>
                       <span class="text-gray-500">{{ $mh->user?->display_name ?? $mh->user?->name ?? '—' }}</span> 
                    </div>

                    <div class="mt-2">
                        <div class="font-semibold uppercase tracking-wide mb-1">
                            Datos de consulta
                        </div>

                        <span class="font-semibold">Hallazgos:</span>
                        <span class="text-gray-500">{{ \Illuminate\Support\Str::limit($mh->findings, 140) ?: '—' }}</span><br>

                        <span class="font-semibold">Tratamiento:</span>
                        <span class="text-gray-500">{{ \Illuminate\Support\Str::limit($mh->tx, 140) ?: '—' }}</span><br>

                        <div >
                            <div>
                                <span class="font-semibold">Refracción:</span>
                                <span class="text-gray-500">
                                    OD: {{ $mh->refraction_od ?? '—' }},
                                    OS: {{ $mh->refraction_os ?? '—' }}
                                    @if(!empty($mh->refraction_add))
                                        · ADD: {{ $mh->refraction_add }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-filament::button size="sm" wire:click="edit({{ $mh->id }})">Editar</x-filament::button>
                    <x-filament::button color="danger" size="sm" wire:click="delete({{ $mh->id }})">Eliminar
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
        @empty
        <x-filament::section>
            <div class="text-sm text-gray-500">No hay consultas registradas.</div>
        </x-filament::section>
        @endforelse
    </div>

    {{ $items->links() }}
</div>