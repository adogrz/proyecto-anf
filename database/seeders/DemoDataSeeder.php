<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sector;
use App\Models\PlantillaCatalogo;
use App\Models\Empresa;
use App\Models\CatalogoCuenta;
use App\Models\EstadoFinanciero;
use App\Models\CuentaBase;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Asegurar que existe un Sector y una Plantilla
        $sector = Sector::firstOrCreate(['nombre' => 'Tecnología']);
        $plantilla = PlantillaCatalogo::first();
        if (!$plantilla) {
            $this->command->error('No se encontró una plantilla de catálogo base. Ejecute CatalogoBaseSeeder primero.');
            return;
        }

        // 2. Crear la Empresa de Demostración
        $empresa = Empresa::create([
            'nombre' => 'Innovatech S.A. de C.V.',
            'sector_id' => $sector->id,
            'plantilla_catalogo_id' => $plantilla->id,
        ]);

        $this->command->info("Empresa de demostración 'Innovatech S.A. de C.V.' creada.");

        // 3. Crear un catálogo de cuentas para la empresa y mapearlo
        $cuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantilla->id)
            ->where('tipo_cuenta', 'DETALLE')
            ->get()
            ->keyBy('nombre');

        $catalogo = [
            ['codigo' => '1101', 'nombre' => 'Efectivo y Equivalentes', 'mapeo' => 'Efectivo y equivalentes al efectivo'],
            ['codigo' => '1102', 'nombre' => 'Cuentas por Cobrar', 'mapeo' => 'Cuentas por cobrar comerciales y otras cuentas por cobrar'],
            ['codigo' => '1201', 'nombre' => 'Propiedad, Planta y Equipo', 'mapeo' => 'Propiedades, planta y equipo'],
            ['codigo' => '2101', 'nombre' => 'Cuentas por Pagar', 'mapeo' => 'Cuentas por pagar comerciales y otras cuentas por pagar'],
            ['codigo' => '2201', 'nombre' => 'Préstamos Bancarios LP', 'mapeo' => 'Pasivos por préstamos a largo plazo'],
            ['codigo' => '3101', 'nombre' => 'Capital Social', 'mapeo' => 'Capital emitido'],
            ['codigo' => '3201', 'nombre' => 'Resultados Acumulados', 'mapeo' => 'Ganancias acumuladas'],
            ['codigo' => '4101', 'nombre' => 'Ingresos por Ventas', 'mapeo' => 'Ingresos de actividades ordinarias'],
            ['codigo' => '5101', 'nombre' => 'Costo de Ventas', 'mapeo' => 'Costo de ventas'],
            ['codigo' => '5201', 'nombre' => 'Gastos de Operación', 'mapeo' => 'Gastos de distribución y administración'],
        ];

        foreach ($catalogo as $cuenta) {
            CatalogoCuenta::create([
                'empresa_id' => $empresa->id,
                'codigo_cuenta' => $cuenta['codigo'],
                'nombre_cuenta' => $cuenta['nombre'],
                'cuenta_base_id' => $cuentasBase->get($cuenta['mapeo'])->id ?? null,
            ]);
        }

        $this->command->info("Catálogo de cuentas de Innovatech creado y mapeado.");

        // 4. Crear un Estado Financiero (Balance General 2023)
        $estadoFinanciero = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2023,
            'tipo_estado' => 'balance_general',
        ]);

        $detalles = [
            'Efectivo y Equivalentes' => 50000,
            'Cuentas por Cobrar' => 120000,
            'Propiedad, Planta y Equipo' => 350000,
            'Cuentas por Pagar' => 80000,
            'Préstamos Bancarios LP' => 150000,
            'Capital Social' => 200000,
            'Resultados Acumulados' => 90000, // Total Activo (520k) - Total Pasivo (230k) - Capital (200k)
        ];

        foreach($detalles as $nombreCuenta => $valor) {
            $catalogoCuenta = CatalogoCuenta::where('empresa_id', $empresa->id)->where('nombre_cuenta', $nombreCuenta)->first();
            if($catalogoCuenta) {
                $estadoFinanciero->detalles()->create([
                    'catalogo_cuenta_id' => $catalogoCuenta->id,
                    'valor' => $valor,
                ]);
            }
        }
        
        $this->command->info("Balance General 2023 para Innovatech creado.");
    }
}
