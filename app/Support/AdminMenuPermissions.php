<?php

namespace App\Support;

use App\Models\AdminRoleMenuAccess;

class AdminMenuPermissions
{
    private const ATTRIBUTE = 'woms.admin_menu_permissions';

    /** @return list<string> */
    public function keys(string $role): array
    {
        // Request attributes also isolate users when running under a long-lived worker.
        $request = request();
        $roles = $request->attributes->get(self::ATTRIBUTE, []);

        if (! array_key_exists($role, $roles)) {
            $roles[$role] = AdminRoleMenuAccess::query()
                ->where('admin_role', $role)
                ->orderBy('menu_key')
                ->pluck('menu_key')->all();
            $request->attributes->set(self::ATTRIBUTE, $roles);
        }

        return $roles[$role];
    }

    public function forget(): void
    {
        request()->attributes->remove(self::ATTRIBUTE);
    }
}
