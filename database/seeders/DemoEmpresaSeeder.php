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

        // 5. Crear Estados Financieros (Balance General y Estado de Resultados) para 4 años
        $balanceGeneral2021 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2021,
            'tipo_estado' => 'balance_general',
        ]);

        $balanceGeneral2022 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2022,
            'tipo_estado' => 'balance_general',
        ]);

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

        $estadoResultados2021 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2021,
            'tipo_estado' => 'estado_resultados',
        ]);

        $estadoResultados2022 = EstadoFinanciero::create([
            'empresa_id' => $empresa->id,
            'anio' => 2022,
            'tipo_estado' => 'estado_resultados',
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

        $this->command->info('Estados Financieros creados: Balance General y Estado de Resultados para 2021-2024');

        // 6. Mapeo de Cuentas de BALANCE GENERAL (con valores para 4 años)
        $cuentasBalanceGeneral = [
            // ACTIVOS
            ['codigo' => '1', 'nombre' => 'ACTIVO', 'base_code' => '1'],
            ['codigo' => '11', 'nombre' => 'ACTIVO CORRIENTE', 'base_code' => '11'],

            // EFECTIVO
            ['codigo' => '1101', 'nombre' => 'EFECTIVO Y EQUIVALENTES DE EFECTIVO', 'base_code' => '1101'],
            ['codigo' => '1101.01', 'nombre' => 'Caja General', 'base_code' => '1101.01', 'val2021' => 12000, 'val2022' => 13500, 'val2023' => 15000, 'val2024' => 18000],
            ['codigo' => '1101.02', 'nombre' => 'Caja Chica', 'base_code' => '1101.02', 'val2021' => 3000, 'val2022' => 4000, 'val2023' => 5000, 'val2024' => 7000],

            ['codigo' => '1101.03', 'nombre' => 'Bancos', 'base_code' => '1101.03'],
            ['codigo' => '1101.03.01', 'nombre' => 'Cuenta Corriente', 'base_code' => '1101.03.01'],
            ['codigo' => '1101.03.01.01', 'nombre' => 'Banco Agrícola', 'base_code' => '1101.03.01.01', 'val2021' => 28000, 'val2022' => 32000, 'val2023' => 35000, 'val2024' => 45000],
            ['codigo' => '1101.03.01.02', 'nombre' => 'Banco Citibank', 'base_code' => '1101.03.01.02', 'val2021' => 20000, 'val2022' => 22000, 'val2023' => 25000, 'val2024' => 30000],

            ['codigo' => '1101.03.02', 'nombre' => 'Cuenta de Ahorro', 'base_code' => '1101.03.02'],
            ['codigo' => '1101.03.02.01', 'nombre' => 'Banco Agrícola', 'base_code' => '1101.03.02.01', 'val2021' => 18000, 'val2022' => 22000, 'val2023' => 25000, 'val2024' => 28000],
            ['codigo' => '1101.03.02.02', 'nombre' => 'Banco Citibank', 'base_code' => '1101.03.02.02', 'val2021' => 12000, 'val2022' => 13000, 'val2023' => 15000, 'val2024' => 17000],

            ['codigo' => '1101.03.03', 'nombre' => 'Depósitos a Plazo Menos de un Año', 'base_code' => '1101.03.03'],
            ['codigo' => '1101.03.03.01', 'nombre' => 'Banco Agrícola', 'base_code' => '1101.03.03.01', 'val2021' => 25000, 'val2022' => 28000, 'val2023' => 30000, 'val2024' => 35000],
            ['codigo' => '1101.03.03.02', 'nombre' => 'Banco Citibank', 'base_code' => '1101.03.03.02', 'val2021' => 15000, 'val2022' => 18000, 'val2023' => 20000, 'val2024' => 25000],

            ['codigo' => '1101.04', 'nombre' => 'Equivalentes de Efectivo', 'base_code' => '1101.04'],
            ['codigo' => '1101.04.01', 'nombre' => 'Reportos', 'base_code' => '1101.04.01', 'val2021' => 8000, 'val2022' => 9000, 'val2023' => 10000, 'val2024' => 12000],
            
            // CUENTAS POR COBRAR
            ['codigo' => '1102', 'nombre' => 'CUENTAS Y DOCUMENTOS POR COBRAR', 'base_code' => '1102'],
            ['codigo' => '1102.01', 'nombre' => 'Clientes', 'base_code' => '1102.01', 'val2021' => 120000, 'val2022' => 135000, 'val2023' => 150000, 'val2024' => 175000],
            ['codigo' => '1102.03', 'nombre' => 'Documentos Por Cobrar', 'base_code' => '1102.03', 'val2021' => 25000, 'val2022' => 28000, 'val2023' => 30000, 'val2024' => 30000],
            ['codigo' => '1102.04', 'nombre' => 'Otras Cuentas por Cobrar', 'base_code' => '1102.04', 'val2021' => 10000, 'val2022' => 12000, 'val2023' => 15000, 'val2024' => 18000],
            ['codigo' => '1102.05', 'nombre' => 'Préstamos a Empleados', 'base_code' => '1102.05', 'val2021' => 5000, 'val2022' => 6500, 'val2023' => 8000, 'val2024' => 10000],
            ['codigo' => '1102.06', 'nombre' => 'Anticipos Sobre Sueldos', 'base_code' => '1102.06', 'val2021' => 3000, 'val2022' => 4000, 'val2023' => 5000, 'val2024' => 6000],

            // INVENTARIOS
            ['codigo' => '1104', 'nombre' => 'INVENTARIOS', 'base_code' => '1104'],
            ['codigo' => '1104.01', 'nombre' => 'Artículos Para El Hogar', 'base_code' => '1104.01'],
            ['codigo' => '1104.01.01', 'nombre' => 'Decoración', 'base_code' => '1104.01.01', 'val2021' => 32000, 'val2022' => 36000, 'val2023' => 40000, 'val2024' => 45000],
            ['codigo' => '1104.01.02', 'nombre' => 'Limpieza', 'base_code' => '1104.01.02', 'val2021' => 28000, 'val2022' => 31000, 'val2023' => 35000, 'val2024' => 38000],
            ['codigo' => '1104.01.03', 'nombre' => 'Cocina', 'base_code' => '1104.01.03', 'val2021' => 38000, 'val2022' => 42000, 'val2023' => 45000, 'val2024' => 47000],
            ['codigo' => '1104.01.04', 'nombre' => 'Muebles', 'base_code' => '1104.01.04', 'val2021' => 25000, 'val2022' => 28000, 'val2023' => 30000, 'val2024' => 30000],
            ['codigo' => '1104.01.05', 'nombre' => 'Otros', 'base_code' => '1104.01.05', 'val2021' => 15000, 'val2022' => 18000, 'val2023' => 20000, 'val2024' => 22000],

            // GASTOS PAGADOS POR ANTICIPADO
            ['codigo' => '1109', 'nombre' => 'GASTOS PAGADOS POR ANTICIPADO', 'base_code' => '1109'],
            ['codigo' => '1109.01', 'nombre' => 'Alquileres', 'base_code' => '1109.01', 'val2021' => 10000, 'val2022' => 12000, 'val2023' => 15000, 'val2024' => 15000],
            ['codigo' => '1109.02', 'nombre' => 'Seguros y Fianzas', 'base_code' => '1109.02', 'val2021' => 8000, 'val2022' => 9000, 'val2023' => 10000, 'val2024' => 12000],
            ['codigo' => '1109.03', 'nombre' => 'Propaganda y Publicidad', 'base_code' => '1109.03', 'val2021' => 5000, 'val2022' => 6000, 'val2023' => 7000, 'val2024' => 8000],

            // ACTIVO NO CORRIENTE
            ['codigo' => '12', 'nombre' => 'ACTIVO NO CORRIENTE', 'base_code' => '12'],
            
            // PROPIEDAD PLANTA Y EQUIPO
            ['codigo' => '1201', 'nombre' => 'PROPIEDAD PLANTA Y EQUIPO', 'base_code' => '1201'],
            ['codigo' => '1201.01', 'nombre' => 'Terrenos', 'base_code' => '1201.01', 'val2021' => 200000, 'val2022' => 200000, 'val2023' => 200000, 'val2024' => 200000],
            ['codigo' => '1201.02', 'nombre' => 'Edificios', 'base_code' => '1201.02', 'val2021' => 150000, 'val2022' => 150000, 'val2023' => 150000, 'val2024' => 150000],
            ['codigo' => '1201.03', 'nombre' => 'Equipo de Transporte', 'base_code' => '1201.03', 'val2021' => 60000, 'val2022' => 70000, 'val2023' => 80000, 'val2024' => 110000],
            ['codigo' => '1201.04', 'nombre' => 'Mobiliario y Equipo de Oficina', 'base_code' => '1201.04', 'val2021' => 50000, 'val2022' => 60000, 'val2023' => 70000, 'val2024' => 70000],
            ['codigo' => '1201.05', 'nombre' => 'Equipo de Cómputo', 'base_code' => '1201.05', 'val2021' => 40000, 'val2022' => 50000, 'val2023' => 60000, 'val2024' => 75000],

            // DEPRECIACIÓN ACUMULADA
            ['codigo' => '1202', 'nombre' => 'DEPRECIACIÓN ACUM. DE PROPIEDAD PLANTA Y EQUIPO', 'base_code' => '1202'],
            ['codigo' => '1202.01', 'nombre' => 'Depreciación Acumulada de Edificios', 'base_code' => '1202.01', 'val2021' => -15000, 'val2022' => -22000, 'val2023' => -30000, 'val2024' => -40000],
            ['codigo' => '1202.02', 'nombre' => 'Depreciación Acumulada de Equipo de Transporte', 'base_code' => '1202.02', 'val2021' => -25000, 'val2022' => -32000, 'val2023' => -40000, 'val2024' => -45000],
            ['codigo' => '1202.03', 'nombre' => 'Depreciación Acumulada de Mobiliario y Equipo', 'base_code' => '1202.03', 'val2021' => -18000, 'val2022' => -24000, 'val2023' => -30000, 'val2024' => -35000],
            ['codigo' => '1202.04', 'nombre' => 'Depreciación Acumulada de Equipo de Cómputo', 'base_code' => '1202.04', 'val2021' => -15000, 'val2022' => -20000, 'val2023' => -25000, 'val2024' => -30000],

            // INVERSIONES
            ['codigo' => '1209', 'nombre' => 'INVERSIONES PERMANENTES', 'base_code' => '1209'],
            ['codigo' => '1209.01', 'nombre' => 'Inversiones en Subsidiarias', 'base_code' => '1209.01', 'val2021' => 50000, 'val2022' => 60000, 'val2023' => 75000, 'val2024' => 80000],

            // PASIVOS
            ['codigo' => '2', 'nombre' => 'PASIVO', 'base_code' => '2'],
            ['codigo' => '21', 'nombre' => 'PASIVO CORRIENTE', 'base_code' => '21'],

            ['codigo' => '2101', 'nombre' => 'PRÉSTAMOS Y SOBREGIROS BANCARIOS', 'base_code' => '2101'],
            ['codigo' => '2101.01', 'nombre' => 'Préstamos Bancarios Corto Plazo', 'base_code' => '2101.01', 'val2021' => 40000, 'val2022' => 45000, 'val2023' => 50000, 'val2024' => 60000],
            ['codigo' => '2101.02', 'nombre' => 'Sobregiros Bancarios', 'base_code' => '2101.02', 'val2021' => 25000, 'val2022' => 28000, 'val2023' => 30000, 'val2024' => 30000],

            ['codigo' => '2102', 'nombre' => 'CUENTAS Y DOCUMENTOS POR PAGAR', 'base_code' => '2102'],
            ['codigo' => '2102.01', 'nombre' => 'Proveedores', 'base_code' => '2102.01'],
            ['codigo' => '2102.01.01', 'nombre' => 'Proveedores Locales', 'base_code' => '2102.01.01', 'val2021' => 55000, 'val2022' => 62000, 'val2023' => 70000, 'val2024' => 75000],
            ['codigo' => '2102.01.02', 'nombre' => 'Proveedores del Exterior', 'base_code' => '2102.01.02', 'val2021' => 22000, 'val2022' => 26000, 'val2023' => 30000, 'val2024' => 35000],
            ['codigo' => '2102.02', 'nombre' => 'Acreedores Diversos', 'base_code' => '2102.02', 'val2021' => 15000, 'val2022' => 18000, 'val2023' => 20000, 'val2024' => 20000],
            
            // BENEFICIOS A EMPLEADOS
            ['codigo' => '2105', 'nombre' => 'BENEFICIOS A EMPLEADOS POR PAGAR', 'base_code' => '2105'],
            ['codigo' => '2105.01', 'nombre' => 'Beneficios a Empleados Por Pagar Corto Plazo', 'base_code' => '2105.01'],
            ['codigo' => '2105.01.04', 'nombre' => 'Vacaciones', 'base_code' => '2105.01.04', 'val2021' => 12000, 'val2022' => 14000, 'val2023' => 16000, 'val2024' => 18000],
            ['codigo' => '2105.01.05', 'nombre' => 'Aguinaldos', 'base_code' => '2105.01.05', 'val2021' => 18000, 'val2022' => 20000, 'val2023' => 22000, 'val2024' => 25000],

            // IMPUESTOS POR PAGAR
            ['codigo' => '2109', 'nombre' => 'IMPUESTOS POR PAGAR', 'base_code' => '2109'],
            ['codigo' => '2109.01', 'nombre' => 'IVA Por Pagar', 'base_code' => '2109.01', 'val2021' => 15000, 'val2022' => 18000, 'val2023' => 20000, 'val2024' => 22000],
            ['codigo' => '2109.02', 'nombre' => 'Impuesto sobre la Renta Corriente', 'base_code' => '2109.02', 'val2021' => 25000, 'val2022' => 28000, 'val2023' => 30000, 'val2024' => 32000],

            // PASIVO NO CORRIENTE
            ['codigo' => '22', 'nombre' => 'PASIVO NO CORRIENTE', 'base_code' => '22'],

            ['codigo' => '2201', 'nombre' => 'PRÉSTAMOS BANCARIOS A LARGO PLAZO', 'base_code' => '2201'],
            ['codigo' => '2201.01', 'nombre' => 'Préstamos Hipotecarios a Largo Plazo', 'base_code' => '2201.01', 'val2021' => 180000, 'val2022' => 170000, 'val2023' => 150000, 'val2024' => 150000],

            // PATRIMONIO
            ['codigo' => '3', 'nombre' => 'PATRIMONIO', 'base_code' => '3'],
            ['codigo' => '31', 'nombre' => 'CAPITAL CONTABLE', 'base_code' => '31'],

            ['codigo' => '3101', 'nombre' => 'CAPITAL SOCIAL', 'base_code' => '3101'],
            ['codigo' => '3101.01', 'nombre' => 'Capital Social Suscrito', 'base_code' => '3101.01', 'val2021' => 250000, 'val2022' => 300000, 'val2023' => 300000, 'val2024' => 300000],

            ['codigo' => '3103', 'nombre' => 'RESERVA LEGAL', 'base_code' => '3103', 'val2021' => 40000, 'val2022' => 45000, 'val2023' => 50000, 'val2024' => 55000],

            ['codigo' => '3104', 'nombre' => 'UTILIDADES POR DISTRIBUIR', 'base_code' => '3104'],
            ['codigo' => '3104.01', 'nombre' => 'Utilidades de Ejercicios Anteriores', 'base_code' => '3104.01', 'val2021' => 60000, 'val2022' => 90000, 'val2023' => 100000, 'val2024' => 150000],
            ['codigo' => '3104.02', 'nombre' => 'Utilidad del Ejercicio', 'base_code' => '3104.02', 'val2021' => 30000, 'val2022' => 40000, 'val2023' => 50000, 'val2024' => 45000],
        ];

        // 7. Mapeo de Cuentas de ESTADO DE RESULTADOS (con valores para 4 años)
        $cuentasEstadoResultados = [
            // INGRESOS
            ['codigo' => '4', 'nombre' => 'INGRESOS', 'base_code' => '4'],
            ['codigo' => '41', 'nombre' => 'INGRESOS OPERACIONALES', 'base_code' => '41'],

            ['codigo' => '4101', 'nombre' => 'INGRESOS POR VENTAS', 'base_code' => '4101'],
            ['codigo' => '4101.01', 'nombre' => 'Ventas de Mercadería', 'base_code' => '4101.01'],
            ['codigo' => '4101.01.01', 'nombre' => 'Decoración', 'base_code' => '4101.01.01', 'val2021' => 380000, 'val2022' => 420000, 'val2023' => 480000, 'val2024' => 520000],
            ['codigo' => '4101.01.02', 'nombre' => 'Limpieza', 'base_code' => '4101.01.02', 'val2021' => 280000, 'val2022' => 310000, 'val2023' => 350000, 'val2024' => 380000],
            ['codigo' => '4101.01.03', 'nombre' => 'Cocina', 'base_code' => '4101.01.03', 'val2021' => 350000, 'val2022' => 385000, 'val2023' => 420000, 'val2024' => 450000],
            ['codigo' => '4101.01.04', 'nombre' => 'Muebles', 'base_code' => '4101.01.04', 'val2021' => 150000, 'val2022' => 165000, 'val2023' => 180000, 'val2024' => 195000],
            ['codigo' => '4101.01.05', 'nombre' => 'Jardinería', 'base_code' => '4101.01.05', 'val2021' => 80000, 'val2022' => 95000, 'val2023' => 110000, 'val2024' => 125000],

            ['codigo' => '4102', 'nombre' => 'DESCUENTOS Y DEVOLUCIONES', 'base_code' => '4102'],
            ['codigo' => '4102.01', 'nombre' => 'Descuentos Sobre Ventas', 'base_code' => '4102.01', 'val2021' => -20000, 'val2022' => -22000, 'val2023' => -25000, 'val2024' => -28000],
            ['codigo' => '4102.02', 'nombre' => 'Devoluciones Sobre Ventas', 'base_code' => '4102.02', 'val2021' => -12000, 'val2022' => -13000, 'val2023' => -15000, 'val2024' => -17000],

            ['codigo' => '4103', 'nombre' => 'OTROS INGRESOS OPERACIONALES', 'base_code' => '4103'],
            ['codigo' => '4103.01', 'nombre' => 'Ingresos por Servicios', 'base_code' => '4103.01', 'val2021' => 25000, 'val2022' => 30000, 'val2023' => 35000, 'val2024' => 40000],

            // GASTOS
            ['codigo' => '5', 'nombre' => 'GASTOS Y COSTOS', 'base_code' => '5'],
            ['codigo' => '51', 'nombre' => 'COSTO DE VENTAS', 'base_code' => '51'],

            ['codigo' => '5101', 'nombre' => 'Costo de Venta de Mercadería', 'base_code' => '5101'],
            ['codigo' => '5101.01', 'nombre' => 'Decoración', 'base_code' => '5101.01', 'val2021' => 228000, 'val2022' => 252000, 'val2023' => 288000, 'val2024' => 312000],
            ['codigo' => '5101.02', 'nombre' => 'Limpieza', 'base_code' => '5101.02', 'val2021' => 168000, 'val2022' => 186000, 'val2023' => 210000, 'val2024' => 228000],
            ['codigo' => '5101.03', 'nombre' => 'Cocina', 'base_code' => '5101.03', 'val2021' => 210000, 'val2022' => 231000, 'val2023' => 252000, 'val2024' => 270000],
            ['codigo' => '5101.04', 'nombre' => 'Muebles', 'base_code' => '5101.04', 'val2021' => 90000, 'val2022' => 99000, 'val2023' => 108000, 'val2024' => 117000],
            ['codigo' => '5101.05', 'nombre' => 'Jardinería', 'base_code' => '5101.05', 'val2021' => 48000, 'val2022' => 57000, 'val2023' => 66000, 'val2024' => 75000],

            // GASTOS DE OPERACIÓN
            ['codigo' => '52', 'nombre' => 'GASTOS DE OPERACIÓN', 'base_code' => '52'],

            ['codigo' => '5201', 'nombre' => 'GASTOS DE VENTA', 'base_code' => '5201'],
            ['codigo' => '5201.01', 'nombre' => 'Sueldos y Salarios', 'base_code' => '5201.01', 'val2021' => 140000, 'val2022' => 160000, 'val2023' => 180000, 'val2024' => 200000],
            ['codigo' => '5201.02', 'nombre' => 'Comisiones Sobre Ventas', 'base_code' => '5201.02', 'val2021' => 48000, 'val2022' => 54000, 'val2023' => 60000, 'val2024' => 65000],
            ['codigo' => '5201.03', 'nombre' => 'Publicidad y Propaganda', 'base_code' => '5201.03', 'val2021' => 18000, 'val2022' => 21000, 'val2023' => 25000, 'val2024' => 28000],
            ['codigo' => '5201.04', 'nombre' => 'Combustible y Lubricantes', 'base_code' => '5201.04', 'val2021' => 10000, 'val2022' => 12000, 'val2023' => 15000, 'val2024' => 18000],
            ['codigo' => '5201.05', 'nombre' => 'Fletes y Acarreos', 'base_code' => '5201.05', 'val2021' => 8000, 'val2022' => 10000, 'val2023' => 12000, 'val2024' => 14000],
            ['codigo' => '5201.06', 'nombre' => 'Empaques y Envíos', 'base_code' => '5201.06', 'val2021' => 5000, 'val2022' => 6000, 'val2023' => 7000, 'val2024' => 8000],

            ['codigo' => '5202', 'nombre' => 'GASTOS DE ADMINISTRACIÓN', 'base_code' => '5202'],
            ['codigo' => '5202.01', 'nombre' => 'Sueldos y Salarios Administrativos', 'base_code' => '5202.01', 'val2021' => 95000, 'val2022' => 105000, 'val2023' => 120000, 'val2024' => 130000],
            ['codigo' => '5202.02', 'nombre' => 'Papelería y Útiles', 'base_code' => '5202.02', 'val2021' => 6000, 'val2022' => 7000, 'val2023' => 8000, 'val2024' => 9000],
            ['codigo' => '5202.03', 'nombre' => 'Energía Eléctrica', 'base_code' => '5202.03', 'val2021' => 9000, 'val2022' => 10000, 'val2023' => 12000, 'val2024' => 13000],
            ['codigo' => '5202.04', 'nombre' => 'Agua', 'base_code' => '5202.04', 'val2021' => 2400, 'val2022' => 2700, 'val2023' => 3000, 'val2024' => 3500],
            ['codigo' => '5202.05', 'nombre' => 'Teléfono e Internet', 'base_code' => '5202.05', 'val2021' => 4800, 'val2022' => 5400, 'val2023' => 6000, 'val2024' => 7000],
            ['codigo' => '5202.06', 'nombre' => 'Alquileres', 'base_code' => '5202.06', 'val2021' => 42000, 'val2022' => 45000, 'val2023' => 48000, 'val2024' => 50000],
            ['codigo' => '5202.07', 'nombre' => 'Seguros y Fianzas', 'base_code' => '5202.07', 'val2021' => 8000, 'val2022' => 9000, 'val2023' => 10000, 'val2024' => 12000],
            ['codigo' => '5202.08', 'nombre' => 'Mantenimiento y Reparaciones', 'base_code' => '5202.08', 'val2021' => 5000, 'val2022' => 6000, 'val2023' => 7000, 'val2024' => 8000],
            ['codigo' => '5202.09', 'nombre' => 'Honorarios Profesionales', 'base_code' => '5202.09', 'val2021' => 12000, 'val2022' => 13000, 'val2023' => 15000, 'val2024' => 16000],
            ['codigo' => '5202.10', 'nombre' => 'Impuestos y Contribuciones', 'base_code' => '5202.10', 'val2021' => 6000, 'val2022' => 7000, 'val2023' => 8000, 'val2024' => 9000],

            // GASTOS FINANCIEROS
            ['codigo' => '53', 'nombre' => 'GASTOS FINANCIEROS', 'base_code' => '53'],

            ['codigo' => '5301', 'nombre' => 'GASTOS FINANCIEROS', 'base_code' => '5301'],
            ['codigo' => '5301.01', 'nombre' => 'Intereses Sobre Préstamos', 'base_code' => '5301.01', 'val2021' => 15000, 'val2022' => 16000, 'val2023' => 18000, 'val2024' => 20000],
            ['codigo' => '5301.02', 'nombre' => 'Comisiones Bancarias', 'base_code' => '5301.02', 'val2021' => 3500, 'val2022' => 4000, 'val2023' => 5000, 'val2024' => 6000],
            ['codigo' => '5301.03', 'nombre' => 'Gastos Bancarios', 'base_code' => '5301.03', 'val2021' => 1500, 'val2022' => 1800, 'val2023' => 2000, 'val2024' => 2500],

            ['codigo' => '5204', 'nombre' => 'OTROS GASTOS', 'base_code' => '5204'],
            ['codigo' => '5204.01', 'nombre' => 'Depreciaciones', 'base_code' => '5204.01', 'val2021' => 20000, 'val2022' => 22000, 'val2023' => 25000, 'val2024' => 30000],
            ['codigo' => '5204.02', 'nombre' => 'Pérdidas en Venta de Activos', 'base_code' => '5204.02', 'val2021' => 2000, 'val2022' => 1500, 'val2023' => 1000, 'val2024' => 1500],
            ['codigo' => '5204.03', 'nombre' => 'Gastos No Deducibles', 'base_code' => '5204.03', 'val2021' => 3000, 'val2022' => 3500, 'val2023' => 4000, 'val2024' => 4500],
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

            // Insertar datos para los 4 años (2021-2024)
            $years = [
                2021 => $balanceGeneral2021,
                2022 => $balanceGeneral2022,
                2023 => $balanceGeneral2023,
                2024 => $balanceGeneral2024,
            ];

            foreach ($years as $year => $estadoFinanciero) {
                if (isset($data["val{$year}"])) {
                    DetalleEstado::create([
                        'estado_financiero_id' => $estadoFinanciero->id,
                        'catalogo_cuenta_id' => $cuentaEmpresa->id,
                        'valor' => $data["val{$year}"],
                    ]);
                }
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

            // Insertar datos para los 4 años (2021-2024)
            $years = [
                2021 => $estadoResultados2021,
                2022 => $estadoResultados2022,
                2023 => $estadoResultados2023,
                2024 => $estadoResultados2024,
            ];

            foreach ($years as $year => $estadoFinanciero) {
                if (isset($data["val{$year}"])) {
                    DetalleEstado::create([
                        'estado_financiero_id' => $estadoFinanciero->id,
                        'catalogo_cuenta_id' => $cuentaEmpresa->id,
                        'valor' => $data["val{$year}"],
                    ]);
                }
            }
        }

        $this->command->info('¡Datos de prueba insertados correctamente!');
        $this->command->info('Balance General - Cuentas: ' . count($cuentasBalanceGeneral));
        $this->command->info('Estado de Resultados - Cuentas: ' . count($cuentasEstadoResultados));
    }
}