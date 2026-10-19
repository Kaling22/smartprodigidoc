<?php

use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Give existing Section Head and Departemen Head accounts the creator workflow. */
    public function up(): void
    {
        $permissions = Permission::whereIn('name', [
            'document.create', 'document.edit', 'document.submit', 'document.delete',
        ])->get();

        foreach ([Roles::ROLE_SECTION_HEAD, Roles::ROLE_DEPARTEMEN_HEAD] as $name) {
            if ($role = Role::where('name', $name)->first()) {
                $role->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = Permission::whereIn('name', [
            'document.create', 'document.edit', 'document.submit', 'document.delete',
        ])->get();

        foreach ([Roles::ROLE_SECTION_HEAD, Roles::ROLE_DEPARTEMEN_HEAD] as $name) {
            if ($role = Role::where('name', $name)->first()) {
                $role->revokePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
