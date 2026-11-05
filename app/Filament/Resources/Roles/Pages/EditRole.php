<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Support\ActivityLogger;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function afterSave(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::record('role.modifie', $this->getRecord(), $this->getRecord()->name, [
            'permissions' => $this->getRecord()->permissions()->pluck('name')->all(),
        ]);
    }
}
