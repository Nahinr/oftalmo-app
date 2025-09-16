<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

       protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        // --- 1) Proteger el rol "Administrator" ---

        // El field "roles" envía IDs (por relationship)
        $incomingRoleIds = $data['roles'] ?? null;
        if (is_array($incomingRoleIds)) {

            $adminRoleId = Role::query()->where('name', 'Administrator')->value('id');

            $recordEsAdminAhora   = $record->hasRole('Administrator');
            $formIncluyeAdminRole = in_array($adminRoleId, $incomingRoleIds, true);

            // a) ¿intentan quitar "Administrator" a este usuario?
            $quitanAdministrator = $recordEsAdminAhora && ! $formIncluyeAdminRole;

            if ($quitanAdministrator) {
                // ¿es el único admin ACTIVO?
                $adminsActivos = User::role('Administrator')->where('status', 'active')->count();

                // i) Bloquea si es el único admin activo
                if ($adminsActivos <= 1) {
                    Notification::make()
                        ->title('No puedes quitar el rol "Administrator" del único Administrador activo.')
                        ->danger()->send();

                    // Reponer el rol en los datos que se guardarán
                    $incomingRoleIds[] = $adminRoleId;
                    $data['roles'] = array_values(array_unique($incomingRoleIds));
                }

                // ii) (Recomendado) Bloquea que un usuario se quite su propio rol admin
                if (Auth::id() === $record->id) {
                    Notification::make()
                        ->title('No puedes quitarte tu propio rol "Administrator".')
                        ->danger()->send();

                    $incomingRoleIds[] = $adminRoleId;
                    $data['roles'] = array_values(array_unique($incomingRoleIds));
                }
            }
        }

        // --- 2) (Opcional) Si también permites cambiar "status", evita dejar inactivo al único admin o auto-inactivarse ---
        if (($data['status'] ?? $record->status) === 'inactive') {
            if (Auth::id() === $record->id) {
                Notification::make()->title('No puedes inactivarte a ti mismo.')->danger()->send();
                $data['status'] = 'active';
            } else {
                $adminsActivos = User::role('Administrator')->where('status', 'active')->count();
                if ($record->hasRole('Administrator') && $adminsActivos <= 1) {
                    Notification::make()->title('No puedes inactivar al único Administrador activo.')->danger()->send();
                    $data['status'] = 'active';
                }
            }
        }

        return $data;
    }
}
