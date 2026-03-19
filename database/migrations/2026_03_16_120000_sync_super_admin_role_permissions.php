<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ]);

        $permissionIds = Permission::query()
            ->where('guard_name', $guardName)
            ->pluck('id')
            ->all();

        $superAdminRole->syncPermissions($permissionIds);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        $superAdminRole = Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', $guardName)
            ->first();

        if ($superAdminRole) {
            $superAdminRole->syncPermissions([]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
