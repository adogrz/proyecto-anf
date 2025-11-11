<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\EstadoFinanciero;
use App\Models\CatalogoCuenta;
use App\Models\DetalleEstado;
use App\Models\CuentaBase;
use App\Models\User;

class DemoEmpresaSeeder extends Seeder
{
    public function run()
    {
        // 1. Asegurar Usuario
        $user = User::findOrFail(2);

        $this->command->info('Usuario asignado: ' . $user->name . ' (ID: ' . $user->id . ')');

        // 2. Asegurar Sector 
        $sector = \App\Models\Sector::firstOrCreate(
            ['id' => 1],
            ['nombre' => 'Tecnología', 'descripcion' => 'Sector de servicios tecnológicos']
        );

        // 3. Asegurar Plantilla de Catálogo
        $plantilla = \App\Models\PlantillaCatalogo::firstOrCreate(
            ['id' => 1],
            ['nombre' => 'Catálogo General', 'descripcion' => 'Plantilla estándar para empresas comerciales']
        );

        // 4. Crear la Empresa Demo usando los IDs dinámicos
        $empresa = Empresa::create([
            'nombre' => 'Tech Solutions SA de CV',
            'sector_id' => $sector->id,
            'usuario_id' => $user->id,
            'plantilla_catalogo_id' => $plantilla->id,
        ]);

        $this->command->info('Empresa creada: ' . $empresa->nombre);

        // 5. Crear Estados Financieros (Balance General y Estado de Resultados)
        $balanceGeneral2023 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2023,
            'tipo_estado' => 'balance_general',
        ]);

        $balanceGeneral2024 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2024,
            'tipo_estado' => 'balance_general',
        ]);

        $estadoResultados2023 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2023,
            'tipo_estado' => 'estado_resultados',
        ]);

        $estadoResultados2024 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2024,
            'tipo_estado' => 'estado_resultados',
        ]);

        $this->command->info('Estados Financieros creados: Balance General y Estado de Resultados para 2023 y 2024');

        // 6. Mapeo de Cuentas de BALANCE GENERAL
        $cuentasBalanceGeneral = [
            // ACTIVOS
            ['codigo' => '1', 'nombre' => 'ACTIVO', 'base_code' => '1'],
            ['codigo' => '11', 'nombre' => 'ACTIVO CORRIENTE', 'base_code' => '11'],

            // EFECTIVO
            ['codigo' => '1101', 'nombre' => 'EFECTIVO Y EQUIVALENTES DE EFECTIVO', 'base_code' => '1101'],
            ['codigo' => '1101.01', 'nombre' => 'Caja General', 'base_code' => '1101.01', 'val2023' => 15000, 'val2024' => 18000],
            ['codigo' => '1101.02', 'nombre' => 'Caja Chica', 'base_code' => '1101.02', 'val2023' => 5000, 'val2024' => 7000],

            ['codigo' => '1101.03', 'nombre' => 'Bancos', 'base_code' => '1101.03'],
            ['codigo' => '1101.03.01', 'nombre' => 'Cuenta Corriente', 'base_code' => '1101.03.01'],
            ['codigo' => '1101.03.01.01', 'nombre' => 'Banco Agrícola', 'base_code' => '1101.03.01.01', 'val2023' => 35000, 'val2024' => 45000],
            ['codigo' => '1101.03.01.02', 'nombre' => 'Banco Citibank', 'base_code' => '1101.03.01.02', 'val2023' => 25000, 'val2024' => 30000],

            ['codigo' => '1101.03.02', 'nombre' => 'Cuenta de Ahorro', 'base_code' => '1101.03.02'],
            ['codigo' => '1101.03.02.01', 'nombre' => 'Banco Agrícola', 'base_code' => '1101.03.02.01', 'val2023' => 25000, 'val2024' => 28000],
            ['codigo' => '1101.03.02.02', 'nombre' => 'Banco Citibank', 'base_code' => '1101.03.02.02', 'val2023' => 15000, 'val2024' => 17000],

            ['codigo' => '1101.03.03', 'nombre' => 'Depósitos a Plazo Menos de un Año', 'base_code' => '1101.03.03'],
            ['codigo' => '1101.03.03.01', 'nombre' => 'Banco Agrícola', 'base_code' => '1101.03.03.01', 'val2023' => 30000, 'val2024' => 35000],
            ['codigo' => '1101.03.03.02', 'nombre' => 'Banco Citibank', 'base_code' => '1101.03.03.02', 'val2023' => 20000, 'val2024' => 25000],

            ['codigo' => '1101.04', 'nombre' => 'Equivalentes de Efectivo', 'base_code' => '1101.04'],
            ['codigo' => '1101.04.01', 'nombre' => 'Reportos', 'base_code' => '1101.04.01', 'val2023' => 10000, 'val2024' => 12000],
            
            // CUENTAS POR COBRAR
            ['codigo' => '1102', 'nombre' => 'CUENTAS Y DOCUMENTOS POR COBRAR', 'base_code' => '1102'],
            ['codigo' => '1102.01', 'nombre' => 'Clientes', 'base_code' => '1102.01', 'val2023' => 150000, 'val2024' => 175000],
            ['codigo' => '1102.03', 'nombre' => 'Documentos Por Cobrar', 'base_code' => '1102.03', 'val2023' => 30000, 'val2024' => 30000],
            ['codigo' => '1102.05', 'nombre' => 'Préstamos a Empleados', 'base_code' => '1102.05', 'val2023' => 8000, 'val2024' => 10000],
            ['codigo' => '1102.06', 'nombre' => 'Anticipos Sobre Sueldos', 'base_code' => '1102.06', 'val2023' => 5000, 'val2024' => 6000],

            // INVENTARIOS
            ['codigo' => '1104', 'nombre' => 'INVENTARIOS', 'base_code' => '1104'],
            ['codigo' => '1104.01', 'nombre' => 'Artículos Para El Hogar', 'base_code' => '1104.01'],
            ['codigo' => '1104.01.01', 'nombre' => 'Decoración', 'base_code' => '1104.01.01', 'val2023' => 40000, 'val2024' => 45000],
            ['codigo' => '1104.01.02', 'nombre' => 'Limpieza', 'base_code' => '1104.01.02', 'val2023' => 35000, 'val2024' => 38000],
            ['codigo' => '1104.01.03', 'nombre' => 'Cocina', 'base_code' => '1104.01.03', 'val2023' => 45000, 'val2024' => 47000],
            ['codigo' => '1104.01.04', 'nombre' => 'Muebles', 'base_code' => '1104.01.04', 'val2023' => 30000, 'val2024' => 30000],

            // ACTIVO NO CORRIENTE
            ['codigo' => '12', 'nombre' => 'ACTIVO NO CORRIENTE', 'base_code' => '12'],
            
            // PROPIEDAD PLANTA Y EQUIPO
            ['codigo' => '1201', 'nombre' => 'PROPIEDAD PLANTA Y EQUIPO', 'base_code' => '1201'],
            ['codigo' => '1201.01', 'nombre' => 'Terrenos', 'base_code' => '1201.01', 'val2023' => 200000, 'val2024' => 200000],
            ['codigo' => '1201.02', 'nombre' => 'Edificios', 'base_code' => '1201.02', 'val2023' => 150000, 'val2024' => 150000],
            ['codigo' => '1201.03', 'nombre' => 'Equipo de Transporte', 'base_code' => '1201.03', 'val2023' => 80000, 'val2024' => 110000],
            ['codigo' => '1201.04', 'nombre' => 'Mobiliario y Equipo de Oficina', 'base_code' => '1201.04', 'val2023' => 70000, 'val2024' => 70000],

            // DEPRECIACIÓN ACUMULADA
            ['codigo' => '1202', 'nombre' => 'DEPRECIACIÓN ACUM. DE PROPIEDAD PLANTA Y EQUIPO', 'base_code' => '1202'],
            ['codigo' => '1202.01', 'nombre' => 'Depreciación Acumulada de Edificios', 'base_code' => '1202.01', 'val2023' => -30000, 'val2024' => -40000],
            ['codigo' => '1202.02', 'nombre' => 'Depreciación Acumulada de Equipo de Transporte', 'base_code' => '1202.02', 'val2023' => -40000, 'val2024' => -45000],
            ['codigo' => '1202.03', 'nombre' => 'Depreciación Acumulada de Mobiliario y Equipo', 'base_code' => '1202.03', 'val2023' => -30000, 'val2024' => -35000],

            // PASIVOS
            ['codigo' => '2', 'nombre' => 'PASIVO', 'base_code' => '2'],
            ['codigo' => '21', 'nombre' => 'PASIVO CORRIENTE', 'base_code' => '21'],

            ['codigo' => '2101', 'nombre' => 'PRÉSTAMOS Y SOBREGIROS BANCARIOS', 'base_code' => '2101'],
            ['codigo' => '2101.01', 'nombre' => 'Préstamos Bancarios Corto Plazo', 'base_code' => '2101.01', 'val2023' => 50000, 'val2024' => 60000],
            ['codigo' => '2101.02', 'nombre' => 'Sobregiros Bancarios', 'base_code' => '2101.02', 'val2023' => 30000, 'val2024' => 30000],

            ['codigo' => '2102', 'nombre' => 'CUENTAS Y DOCUMENTOS POR PAGAR', 'base_code' => '2102'],
            ['codigo' => '2102.01', 'nombre' => 'Proveedores', 'base_code' => '2102.01'],
            ['codigo' => '2102.01.01', 'nombre' => 'Proveedores Locales', 'base_code' => '2102.01.01', 'val2023' => 70000, 'val2024' => 75000],
            ['codigo' => '2102.01.02', 'nombre' => 'Proveedores del Exterior', 'base_code' => '2102.01.02', 'val2023' => 30000, 'val2024' => 35000],
            ['codigo' => '2102.02', 'nombre' => 'Acreedores Diversos', 'base_code' => '2102.02', 'val2023' => 20000, 'val2024' => 20000],
            
            // PASIVO NO CORRIENTE
            ['codigo' => '22', 'nombre' => 'PASIVO NO CORRIENTE', 'base_code' => '22'],

            ['codigo' => '2201', 'nombre' => 'PRÉSTAMOS BANCARIOS A LARGO PLAZO', 'base_code' => '2201'],
            ['codigo' => '2201.01', 'nombre' => 'Préstamos Hipotecarios a Largo Plazo', 'base_code' => '2201.01', 'val2023' => 150000, 'val2024' => 150000],

            // PATRIMONIO
            ['codigo' => '3', 'nombre' => 'PATRIMONIO', 'base_code' => '3'],
            ['codigo' => '31', 'nombre' => 'CAPITAL CONTABLE', 'base_code' => '31'],

            ['codigo' => '3101', 'nombre' => 'CAPITAL SOCIAL', 'base_code' => '3101'],
            ['codigo' => '3101.01', 'nombre' => 'Capital Social Suscrito', 'base_code' => '3101.01', 'val2023' => 300000, 'val2024' => 300000],

            ['codigo' => '3103', 'nombre' => 'RESERVA LEGAL', 'base_code' => '3103', 'val2023' => 50000, 'val2024' => 55000],

            ['codigo' => '3104', 'nombre' => 'UTILIDADES POR DISTRIBUIR', 'base_code' => '3104'],
            ['codigo' => '3104.01', 'nombre' => 'Utilidades de Ejercicios Anteriores', 'base_code' => '3104.01', 'val2023' => 100000, 'val2024' => 150000],
            ['codigo' => '3104.02', 'nombre' => 'Utilidad del Ejercicio', 'base_code' => '3104.02', 'val2023' => 50000, 'val2024' => 45000],
        ];

        // 7. Mapeo de Cuentas de ESTADO DE RESULTADOS
        $cuentasEstadoResultados = [
            // INGRESOS
            ['codigo' => '4', 'nombre' => 'INGRESOS', 'base_code' => '4'],
            ['codigo' => '41', 'nombre' => 'INGRESOS OPERACIONALES', 'base_code' => '41'],

            ['codigo' => '4101', 'nombre' => 'VENTAS', 'base_code' => '4101'],
            ['codigo' => '4101.01', 'nombre' => 'Ventas de Productos', 'base_code' => '4101.01'],
            ['codigo' => '4101.01.01', 'nombre' => 'Ventas Nacionales', 'base_code' => '4101.01.01', 'val2023' => 800000, 'val2024' => 950000],
            ['codigo' => '4101.01.02', 'nombre' => 'Ventas Exportación', 'base_code' => '4101.01.02', 'val2023' => 200000, 'val2024' => 250000],

            ['codigo' => '4102', 'nombre' => 'SERVICIOS', 'base_code' => '4102'],
            ['codigo' => '4102.01', 'nombre' => 'Ingresos por Servicios Tecnológicos', 'base_code' => '4102.01', 'val2023' => 150000, 'val2024' => 180000],
            ['codigo' => '4102.02', 'nombre' => 'Ingresos por Consultoría', 'base_code' => '4102.02', 'val2023' => 100000, 'val2024' => 120000],

            // GASTOS
            ['codigo' => '5', 'nombre' => 'GASTOS', 'base_code' => '5'],
            ['codigo' => '51', 'nombre' => 'COSTO DE VENTAS', 'base_code' => '51'],

            ['codigo' => '5101', 'nombre' => 'COSTO DE VENTAS', 'base_code' => '5101'],
            ['codigo' => '5101.01', 'nombre' => 'Costo de Productos Vendidos', 'base_code' => '5101.01', 'val2023' => 400000, 'val2024' => 480000],
            ['codigo' => '5101.02', 'nombre' => 'Costo de Servicios Prestados', 'base_code' => '5101.02', 'val2023' => 80000, 'val2024' => 95000],

            // GASTOS DE OPERACIÓN
            ['codigo' => '52', 'nombre' => 'GASTOS DE OPERACIÓN', 'base_code' => '52'],

            ['codigo' => '5201', 'nombre' => 'GASTOS DE VENTAS', 'base_code' => '5201'],
            ['codigo' => '5201.01', 'nombre' => 'Sueldos y Salarios de Ventas', 'base_code' => '5201.01', 'val2023' => 120000, 'val2024' => 140000],
            ['codigo' => '5201.02', 'nombre' => 'Comisiones sobre Ventas', 'base_code' => '5201.02', 'val2023' => 50000, 'val2024' => 60000],
            ['codigo' => '5201.03', 'nombre' => 'Publicidad y Promoción', 'base_code' => '5201.03', 'val2023' => 30000, 'val2024' => 35000],
            ['codigo' => '5201.04', 'nombre' => 'Transporte y Entrega', 'base_code' => '5201.04', 'val2023' => 25000, 'val2024' => 28000],

            ['codigo' => '5202', 'nombre' => 'GASTOS DE ADMINISTRACIÓN', 'base_code' => '5202'],
            ['codigo' => '5202.01', 'nombre' => 'Sueldos Personal Administrativo', 'base_code' => '5202.01', 'val2023' => 180000, 'val2024' => 200000],
            ['codigo' => '5202.02', 'nombre' => 'Prestaciones Laborales', 'base_code' => '5202.02', 'val2023' => 40000, 'val2024' => 45000],
            ['codigo' => '5202.03', 'nombre' => 'Servicios Profesionales', 'base_code' => '5202.03', 'val2023' => 35000, 'val2024' => 40000],
            ['codigo' => '5202.04', 'nombre' => 'Arrendamientos', 'base_code' => '5202.04', 'val2023' => 60000, 'val2024' => 60000],
            ['codigo' => '5202.05', 'nombre' => 'Energía Eléctrica', 'base_code' => '5202.05', 'val2023' => 18000, 'val2024' => 20000],
            ['codigo' => '5202.06', 'nombre' => 'Agua', 'base_code' => '5202.06', 'val2023' => 6000, 'val2024' => 6500],
            ['codigo' => '5202.07', 'nombre' => 'Teléfono e Internet', 'base_code' => '5202.07', 'val2023' => 12000, 'val2024' => 13000],
            ['codigo' => '5202.08', 'nombre' => 'Papelería y Útiles', 'base_code' => '5202.08', 'val2023' => 8000, 'val2024' => 9000],
            ['codigo' => '5202.09', 'nombre' => 'Depreciaciones', 'base_code' => '5202.09', 'val2023' => 25000, 'val2024' => 30000],
            ['codigo' => '5202.10', 'nombre' => 'Seguros', 'base_code' => '5202.10', 'val2023' => 15000, 'val2024' => 16000],
            ['codigo' => '5202.11', 'nombre' => 'Mantenimiento y Reparaciones', 'base_code' => '5202.11', 'val2023' => 20000, 'val2024' => 22000],

            // GASTOS FINANCIEROS
            ['codigo' => '53', 'nombre' => 'GASTOS FINANCIEROS', 'base_code' => '53'],

            ['codigo' => '5301', 'nombre' => 'GASTOS FINANCIEROS', 'base_code' => '5301'],
            ['codigo' => '5301.01', 'nombre' => 'Intereses sobre Préstamos', 'base_code' => '5301.01', 'val2023' => 18000, 'val2024' => 20000],
            ['codigo' => '5301.02', 'nombre' => 'Comisiones Bancarias', 'base_code' => '5301.02', 'val2023' => 5000, 'val2024' => 5500],
        ];

        // 8. Insertar cuentas de Balance General
        $this->command->info('Insertando cuentas de Balance General...');
        foreach ($cuentasBalanceGeneral as $data) {
            $cuentaBase = CuentaBase::where('codigo', $data['base_code'])->first();

            if (!$cuentaBase) {
                $this->command->warn("No se encontró cuenta base con código {$data['base_code']}. Saltando...");
                continue;
            }

            $cuentaEmpresa = CatalogoCuenta::create([
                'empresa_id' => $empresa->id,
                'cuenta_base_id' => $cuentaBase->id,
                'codigo_cuenta' => $data['codigo'],
                'nombre_cuenta' => $data['nombre'],
            ]);

            if (isset($data['val2023']) && isset($data['val2024'])) {
                DetalleEstado::create([
                    'estado_financiero_id' => $balanceGeneral2023->id,
                    'catalogo_cuenta_id' => $cuentaEmpresa->id,
                    'valor' => $data['val2023'],
                ]);

                DetalleEstado::create([
                    'estado_financiero_id' => $balanceGeneral2024->id,
                    'catalogo_cuenta_id' => $cuentaEmpresa->id,
                    'valor' => $data['val2024'],
                ]);
            }
        }

        // 9. Insertar cuentas de Estado de Resultados
        $this->command->info('Insertando cuentas de Estado de Resultados...');
        foreach ($cuentasEstadoResultados as $data) {
            $cuentaBase = CuentaBase::where('codigo', $data['base_code'])->first();

            if (!$cuentaBase) {
                $this->command->warn("No se encontró cuenta base con código {$data['base_code']}. Saltando...");
                continue;
            }

            // Verificar si la cuenta ya existe (para evitar duplicados)
            $cuentaExistente = CatalogoCuenta::where('empresa_id', $empresa->id)
                ->where('codigo_cuenta', $data['codigo'])
                ->first();

            if ($cuentaExistente) {
                $cuentaEmpresa = $cuentaExistente;
            } else {
                $cuentaEmpresa = CatalogoCuenta::create([
                    'empresa_id' => $empresa->id,
                    'cuenta_base_id' => $cuentaBase->id,
                    'codigo_cuenta' => $data['codigo'],
                    'nombre_cuenta' => $data['nombre'],
                ]);
            }

            if (isset($data['val2023']) && isset($data['val2024'])) {
                DetalleEstado::create([
                    'estado_financiero_id' => $estadoResultados2023->id,
                    'catalogo_cuenta_id' => $cuentaEmpresa->id,
                    'valor' => $data['val2023'],
                ]);

                DetalleEstado::create([
                    'estado_financiero_id' => $estadoResultados2024->id,
                    'catalogo_cuenta_id' => $cuentaEmpresa->id,
                    'valor' => $data['val2024'],
                ]);
            }
        }

        $this->command->info('¡Datos de prueba insertados correctamente!');
        $this->command->info('Balance General - Cuentas: ' . count($cuentasBalanceGeneral));
        $this->command->info('Estado de Resultados - Cuentas: ' . count($cuentasEstadoResultados));
    }
}