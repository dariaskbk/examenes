<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::updateOrCreate(
            ['name' => 'View sensitive student data', 'guard_name' => 'web'],
            ['display_name' => 'Ver datos sensibles del estudiante']
        );

        Role::whereIn('name', ['Super Admin', 'Admin', 'Principal'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::where('name', 'View sensitive student data')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            Role::all()->each(fn (Role $role) => $role->revokePermissionTo($permission));
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
