<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names', []);
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');
        $permissionId = DB::table($permissionsTable)
            ->where('name', 'show_contact_submissions')
            ->where('guard_name', $guardName)
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table($permissionsTable)->insertGetId([
                'name' => 'show_contact_submissions',
                'parent' => 'marketing',
                'guard_name' => $guardName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable($rolesTable) || ! Schema::hasTable($roleHasPermissionsTable)) {
            return;
        }

        $roleId = DB::table($rolesTable)->where([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ])->value('id');

        if (! $roleId) {
            return;
        }

        $alreadyAssigned = DB::table($roleHasPermissionsTable)
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (! $alreadyAssigned) {
            DB::table($roleHasPermissionsTable)->insert([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names', []);
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        $guardName = config('auth.defaults.guard', 'web');
        $permissionIds = DB::table($permissionsTable)
            ->where('guard_name', $guardName)
            ->where('name', 'show_contact_submissions')
            ->pluck('id')
            ->all();

        if ($permissionIds !== [] && Schema::hasTable($roleHasPermissionsTable)) {
            DB::table($roleHasPermissionsTable)->whereIn('permission_id', $permissionIds)->delete();
        }

        if ($permissionIds !== []) {
            DB::table($permissionsTable)->whereIn('id', $permissionIds)->delete();
        }
    }
};
