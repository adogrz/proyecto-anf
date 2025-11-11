<?php

namespace Database\Seeders;

use App\Models\RatioSector;
use App\Traits\TieneRatiosFinancieros;
use Illuminate\Database\Seeder;
use App\Models\Sector;

class RatiosSectorSeeder extends Seeder
{
    use TieneRatiosFinancieros;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando RatiosSectorSeeder...');

        // 1) Crear / asegurar sectores
        $sectorMineria = Sector::firstOrCreate(
            ['nombre' => 'Minería'],
            ['descripcion' => 'Sector de extracción y procesamiento de minerales']
        );
        $sectorComercio = Sector::firstOrCreate(
            ['nombre' => 'Comercio'],
            ['descripcion' => 'Sector de comercialización y distribución']
        );

        $this->command->info("📊 Sectores: Minería(ID {$sectorMineria->id}), Comercio(ID {$sectorComercio->id})");

        // 2) Ratios por sector (sin IDs fijos)
        $ratiosPorSector = [
            [
                'sector' => $sectorMineria,
                'ratios' => [
                    [ 'nombre_ratio' => self::RAZON_CIRCULANTE, 'valor_referencia' => 1.50, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::PRUEBA_ACIDA, 'valor_referencia' => 0.55, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::CAPITAL_TRABAJO, 'valor_referencia' => 500000, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::ROTACION_INVENTARIO, 'valor_referencia' => 4.50, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::DIAS_INVENTARIO, 'valor_referencia' => 81.11, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::ROTACION_ACTIVOS, 'valor_referencia' => 0.85, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::GRADO_ENDEUDAMIENTO, 'valor_referencia' => 40.00, 'fuente' => 'Ministerio de Economía Chile' ],
                    [ 'nombre_ratio' => self::ENDEUDAMIENTO_PATRIMONIAL, 'valor_referencia' => 0.67, 'fuente' => 'Almanac of Business and Industrial Financial Ratios' ],
                    [ 'nombre_ratio' => self::ROE, 'valor_referencia' => 15.00, 'fuente' => 'Almanac of Business and Industrial Financial Ratios' ],
                    [ 'nombre_ratio' => self::ROA, 'valor_referencia' => 8.50, 'fuente' => 'Almanac of Business and Industrial Financial Ratios' ],
                ],
            ],
            [
                'sector' => $sectorComercio,
                'ratios' => [
                    [ 'nombre_ratio' => self::RAZON_CIRCULANTE, 'valor_referencia' => 1.80, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::PRUEBA_ACIDA, 'valor_referencia' => 0.90, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::CAPITAL_TRABAJO, 'valor_referencia' => 300000, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::ROTACION_INVENTARIO, 'valor_referencia' => 6.20, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::DIAS_INVENTARIO, 'valor_referencia' => 58.87, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::ROTACION_ACTIVOS, 'valor_referencia' => 1.20, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::GRADO_ENDEUDAMIENTO, 'valor_referencia' => 45.00, 'fuente' => 'Superintendencia de Sociedades Colombia' ],
                    [ 'nombre_ratio' => self::ENDEUDAMIENTO_PATRIMONIAL, 'valor_referencia' => 0.82, 'fuente' => 'Almanac of Business and Industrial Financial Ratios' ],
                    [ 'nombre_ratio' => self::ROE, 'valor_referencia' => 18.00, 'fuente' => 'Almanac of Business and Industrial Financial Ratios' ],
                    [ 'nombre_ratio' => self::ROA, 'valor_referencia' => 10.00, 'fuente' => 'Almanac of Business and Industrial Financial Ratios' ],
                ],
            ],
        ];

        // 3) Inserción idempotente
        $this->command->info('💾 Insertando ratios por sector...');
        $total = 0;
        foreach ($ratiosPorSector as $sectorData) {
            $sector = $sectorData['sector'];
            foreach ($sectorData['ratios'] as $ratio) {
                RatioSector::updateOrCreate(
                    [
                        'sector_id' => $sector->id,
                        'nombre_ratio' => $ratio['nombre_ratio'],
                    ],
                    [
                        'valor_referencia' => $ratio['valor_referencia'],
                        'fuente' => $ratio['fuente'],
                    ]
                );
                $total++;
            }
        }

        $this->command->info("✅ {$total} ratios por sector insertados/actualizados");
        $this->command->info('🎉 RatiosSectorSeeder completado');
    }
}
