<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names', []);
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        if (
            ! Schema::hasTable($rolesTable) ||
            ! Schema::hasTable($permissionsTable) ||
            ! Schema::hasTable($roleHasPermissionsTable)
        ) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');

        $roleId = DB::table($rolesTable)->where([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ])->value('id');

        if (! $roleId) {
            $roleId = DB::table($rolesTable)->insertGetId([
                'name' => 'Super Admin',
                'guard_name' => $guardName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionIds = DB::table($permissionsTable)
            ->where('guard_name', $guardName)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::table($roleHasPermissionsTable)->where('role_id', $roleId)->delete();

        if ($permissionIds === []) {
            return;
        }

        DB::table($roleHasPermissionsTable)->insert(
            collect($permissionIds)->map(fn (int $permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ])->all()
        );
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', []);
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        if (! Schema::hasTable($rolesTable) || ! Schema::hasTable($roleHasPermissionsTable)) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');

        $roleId = DB::table($rolesTable)->where([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ])->value('id');

        if (! $roleId) {
            return;
        }

        DB::table($roleHasPermissionsTable)->where('role_id', $roleId)->delete();
    }
};
