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
            'administracion.index',
            'catalogos.index',
            'catalogos.create',
            'catalogos.edit',
            'catalogos.delete',
            'sectores.index',
            'sectores.create',
            'sectores.edit',
            'sectores.delete',
            'cuentas-base.index',
            'cuentas-base.create',
            'cuentas-base.edit',
            'cuentas-base.delete',
            'empresas.index',
            'empresas.create',
            'empresas.edit',
            'empresas.delete',
            'ratios.index',
            'ratios.create',
            'ratios.edit',
            'ratios.delete',
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
