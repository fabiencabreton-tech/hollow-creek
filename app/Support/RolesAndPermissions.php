<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Met en place les rôles et permissions décrits dans config/hollow.php.
 * Sans danger à relancer : rien n'est supprimé, et les permissions d'un rôle
 * modifiées dans l'administration ne sont pas écrasées (sauf « administrateur »,
 * qui reçoit toujours toutes les permissions).
 */
class RolesAndPermissions
{
    /**
     * @return array{permissions: int, roles: int}
     */
    public static function sync(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = ['permissions' => 0, 'roles' => 0];

        foreach (array_keys((array) config('hollow.permissions')) as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            $created['permissions'] += $permission->wasRecentlyCreated ? 1 : 0;
        }

        foreach ((array) config('hollow.roles') as $name => $definition) {
            $role = Role::findOrCreate($name, 'web');

            if ($definition['permissions'] === '*') {
                $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
            } elseif ($role->wasRecentlyCreated) {
                $role->syncPermissions($definition['permissions']);
            }

            $created['roles'] += $role->wasRecentlyCreated ? 1 : 0;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }
}
