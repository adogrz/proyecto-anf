<?php

namespace Database\Seeders;

use App\Models\DatoVentaHistorico;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class DatosHistoricosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar la empresa Innovatech
        $empresa = Empresa::where('nombre', 'like', '%Innovatech%')->first();

        if (!$empresa) {
            $this->command->error('No se encontró la empresa Innovatech. Ejecute DemoDataSeeder primero.');
            return;
        }

        // Datos históricos: 36 períodos mensuales (3 años)
        // x representa el período secuencial, y el monto de ventas
        $datosHistoricos = [
            ['x' => 1, 'y' => 100000.00],
            ['x' => 2, 'y' => 110000.00],
            ['x' => 3, 'y' => 125000.00],
            ['x' => 4, 'y' => 128000.00],
            ['x' => 5, 'y' => 132000.00],
            ['x' => 6, 'y' => 136000.00],
            ['x' => 7, 'y' => 139000.00],
            ['x' => 8, 'y' => 200000.00],
            ['x' => 9, 'y' => 220000.00],
            ['x' => 10, 'y' => 221000.00],
            ['x' => 11, 'y' => 225000.00],
            ['x' => 12, 'y' => 229000.00],
            ['x' => 13, 'y' => 217000.00],
            ['x' => 14, 'y' => 221000.00],
            ['x' => 15, 'y' => 229000.00],
            ['x' => 16, 'y' => 245000.00],
            ['x' => 17, 'y' => 251000.00],
            ['x' => 18, 'y' => 255000.00],
            ['x' => 19, 'y' => 267000.00],
            ['x' => 20, 'y' => 271000.00],
            ['x' => 21, 'y' => 280000.00],
            ['x' => 22, 'y' => 289000.00],
            ['x' => 23, 'y' => 294000.00],
            ['x' => 24, 'y' => 299000.00],
            ['x' => 25, 'y' => 281000.00],
            ['x' => 26, 'y' => 288000.00],
            ['x' => 27, 'y' => 295000.00],
            ['x' => 28, 'y' => 299000.00],
            ['x' => 29, 'y' => 302000.00],
            ['x' => 30, 'y' => 311000.00],
            ['x' => 31, 'y' => 320000.00],
            ['x' => 32, 'y' => 331000.00],
            ['x' => 33, 'y' => 338000.00],
            ['x' => 34, 'y' => 342000.00],
            ['x' => 35, 'y' => 356000.00],
            ['x' => 36, 'y' => 370000.00],
        ];

        // Año inicial: 2022
        $anioInicial = 2022;
        $mesInicial = 1;

        foreach ($datosHistoricos as $dato) {
            // Calcular año y mes basado en el período x
            $periodoDesde2022 = $dato['x'] - 1; // 0-based
            $anio = $anioInicial + floor($periodoDesde2022 / 12);
            $mes = ($mesInicial + ($periodoDesde2022 % 12));

            DatoVentaHistorico::create([
                'empresa_id' => $empresa->id,
                'anio' => $anio,
                'mes' => $mes,
                'monto' => $dato['y'],
            ]);
        }

        $this->command->info("✅ Se crearon {$dato['x']} registros de datos históricos para Innovatech.");
        $this->command->info("   Período: Enero 2022 - Diciembre 2024");
    }
}
