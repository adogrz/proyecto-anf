<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permission names
        $permissions = [
            'catalogos.index',
            'catalogos.create',
            'catalogos.edit',
            'catalogos.delete',
            'tipos-empresa.index',
            'tipos-empresa.create',
            'tipos-empresa.edit',
            'tipos-empresa.delete',
            'empresas.index',
            'empresas.create',
            'empresas.edit',
            'empresas.delete',
            'ratios-generales.index',
            'ratios-generales.create',
            'ratios-generales.edit',
            'ratios-generales.delete',
            'estados-financieros.index',
            'estados-financieros.create',
            'estados-financieros.edit',
            'estados-financieros.delete',
            'informes.index',
            'analisis-vertical.index',
            'analisis-horizontal.index',
            'ratios-financieros.index',
            'proyecciones.index',
            'proyecciones.create',
            'proyecciones.edit',
            'proyecciones.delete',
            'analisis-proforma.index',
            'analisis-proforma.create',
            'analisis-proforma.edit',
            'analisis-proforma.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Analista de Datos Role
        $analistaRole = Role::create(['name' => 'Analista de Datos']);
        $analistaRole->givePermissionTo([
            'estados-financieros.index',
            'estados-financieros.create',
            'informes.index',
            'analisis-vertical.index',
            'analisis-horizontal.index',
            'ratios-financieros.index',
        ]);

        // Gerente Financiero Role
        $gerenteRole = Role::create(['name' => 'Gerente Financiero']);
        $gerenteRole->givePermissionTo($analistaRole->permissions);
        $gerenteRole->givePermissionTo([
            'estados-financieros.edit',
            'estados-financieros.delete',
            'proyecciones.index',
            'proyecciones.create',
            'proyecciones.edit',
            'proyecciones.delete',
            'analisis-proforma.index',
            'analisis-proforma.create',
            'analisis-proforma.edit',
            'analisis-proforma.delete',
        ]);

        // Auditor Role
        $auditorRole = Role::create(['name' => 'Auditor']);
        $auditorRole->givePermissionTo([
            'estados-financieros.index',
            'informes.index',
            'analisis-vertical.index',
            'analisis-horizontal.index',
            'ratios-financieros.index',
        ]);

        // Administrador Role
        $adminRole = Role::create(['name' => 'Administrador']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
