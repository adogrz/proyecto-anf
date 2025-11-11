<?php

namespace Database\Seeders;

use App\Models\RatioCalculado;
use App\Traits\TieneRatiosFinancieros;
use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use App\Models\PlantillaCatalogo;

class RatiosCalculadosSeeder extends Seeder
{
    use TieneRatiosFinancieros;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando RatiosCalculadosSeeder...');

        // 1) Usuario demo para asociar empresas
        $usuario = User::firstOrCreate(
            ['email' => 'demo.ratios@example.com'],
            [
                'name' => 'Demo Ratios User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2) Sectores requeridos
        $sectorMineria = Sector::firstOrCreate(
            ['nombre' => 'Minería'],
            ['descripcion' => 'Sector de extracción y procesamiento de minerales']
        );
        $sectorComercio = Sector::firstOrCreate(
            ['nombre' => 'Comercio'],
            ['descripcion' => 'Sector de comercialización y distribución']
        );

        // 3) Plantilla requerida
        $plantilla = PlantillaCatalogo::firstOrCreate(
            ['nombre' => 'Catálogo General Demo'],
            ['descripcion' => 'Plantilla estándar para empresas demo']
        );

        // 4) Empresas necesarias (2 mineras + 2 comerciales)
        $minera1 = Empresa::firstOrCreate(
            ['nombre' => 'Minera XYZ'],
            [
                'sector_id' => $sectorMineria->id,
                'usuario_id' => $usuario->id,
                'plantilla_catalogo_id' => $plantilla->id,
            ]
        );
        $minera2 = Empresa::firstOrCreate(
            ['nombre' => 'Minera Los Andes'],
            [
                'sector_id' => $sectorMineria->id,
                'usuario_id' => $usuario->id,
                'plantilla_catalogo_id' => $plantilla->id,
            ]
        );
        $comercial1 = Empresa::firstOrCreate(
            ['nombre' => 'Comercial ABC'],
            [
                'sector_id' => $sectorComercio->id,
                'usuario_id' => $usuario->id,
                'plantilla_catalogo_id' => $plantilla->id,
            ]
        );
        $comercial2 = Empresa::firstOrCreate(
            ['nombre' => 'Distribuidora XYZ'],
            [
                'sector_id' => $sectorComercio->id,
                'usuario_id' => $usuario->id,
                'plantilla_catalogo_id' => $plantilla->id,
            ]
        );

        $this->command->info("📊 Empresas listas: {$minera1->nombre}, {$minera2->nombre}, {$comercial1->nombre}, {$comercial2->nombre}");

        // 5) Datos de ratios (sin IDs fijos)
        $datosEmpresas = [
            // EMPRESA 1: Minera XYZ
            [
                'empresa' => $minera1,
                'anios' => [
                    2022 => [
                        self::RAZON_CIRCULANTE => 1.10,
                        self::PRUEBA_ACIDA => 0.65,
                        self::ROTACION_INVENTARIO => 3.50,
                        self::DIAS_INVENTARIO => 104.29,
                        self::ROTACION_ACTIVOS => 0.75,
                        self::GRADO_ENDEUDAMIENTO => 42.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.72,
                        self::ROE => 12.50,
                        self::ROA => 7.20,
                        self::CAPITAL_TRABAJO => 200000,
                    ],
                    2023 => [
                        self::RAZON_CIRCULANTE => 1.18,
                        self::PRUEBA_ACIDA => 0.72,
                        self::ROTACION_INVENTARIO => 4.00,
                        self::DIAS_INVENTARIO => 91.25,
                        self::ROTACION_ACTIVOS => 0.80,
                        self::GRADO_ENDEUDAMIENTO => 41.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.69,
                        self::ROE => 14.00,
                        self::ROA => 8.00,
                        self::CAPITAL_TRABAJO => 300000,
                    ],
                    2024 => [
                        self::RAZON_CIRCULANTE => 1.25,
                        self::PRUEBA_ACIDA => 0.85,
                        self::ROTACION_INVENTARIO => 4.50,
                        self::DIAS_INVENTARIO => 81.11,
                        self::ROTACION_ACTIVOS => 0.85,
                        self::GRADO_ENDEUDAMIENTO => 40.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.67,
                        self::ROE => 15.00,
                        self::ROA => 9.00,
                        self::CAPITAL_TRABAJO => 500000,
                    ]
                ]
            ],

            // EMPRESA 2: Minera Los Andes
            [
                'empresa' => $minera2,
                'anios' => [
                    2022 => [
                        self::RAZON_CIRCULANTE => 1.35,
                        self::PRUEBA_ACIDA => 0.75,
                        self::ROTACION_INVENTARIO => 3.80,
                        self::DIAS_INVENTARIO => 96.05,
                        self::ROTACION_ACTIVOS => 0.78,
                        self::GRADO_ENDEUDAMIENTO => 43.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.75,
                        self::ROE => 13.00,
                        self::ROA => 7.50,
                        self::CAPITAL_TRABAJO => 450000,
                    ],
                    2023 => [
                        self::RAZON_CIRCULANTE => 1.45,
                        self::PRUEBA_ACIDA => 0.85,
                        self::ROTACION_INVENTARIO => 4.20,
                        self::DIAS_INVENTARIO => 86.90,
                        self::ROTACION_ACTIVOS => 0.82,
                        self::GRADO_ENDEUDAMIENTO => 44.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.79,
                        self::ROE => 13.50,
                        self::ROA => 7.80,
                        self::CAPITAL_TRABAJO => 550000,
                    ],
                    2024 => [
                        self::RAZON_CIRCULANTE => 1.55,
                        self::PRUEBA_ACIDA => 0.95,
                        self::ROTACION_INVENTARIO => 4.80,
                        self::DIAS_INVENTARIO => 76.04,
                        self::ROTACION_ACTIVOS => 0.85,
                        self::GRADO_ENDEUDAMIENTO => 45.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.82,
                        self::ROE => 14.00,
                        self::ROA => 8.00,
                        self::CAPITAL_TRABAJO => 600000,
                    ]
                ]
            ],

            // EMPRESA 3: Comercial ABC
            [
                'empresa' => $comercial1,
                'anios' => [
                    2022 => [
                        self::RAZON_CIRCULANTE => 1.65,
                        self::PRUEBA_ACIDA => 0.80,
                        self::ROTACION_INVENTARIO => 5.80,
                        self::DIAS_INVENTARIO => 62.93,
                        self::ROTACION_ACTIVOS => 1.15,
                        self::GRADO_ENDEUDAMIENTO => 46.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.85,
                        self::ROE => 16.00,
                        self::ROA => 9.00,
                        self::CAPITAL_TRABAJO => 250000,
                    ],
                    2023 => [
                        self::RAZON_CIRCULANTE => 1.72,
                        self::PRUEBA_ACIDA => 0.85,
                        self::ROTACION_INVENTARIO => 6.00,
                        self::DIAS_INVENTARIO => 60.83,
                        self::ROTACION_ACTIVOS => 1.18,
                        self::GRADO_ENDEUDAMIENTO => 45.50,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.83,
                        self::ROE => 17.00,
                        self::ROA => 9.50,
                        self::CAPITAL_TRABAJO => 280000,
                    ],
                    2024 => [
                        self::RAZON_CIRCULANTE => 1.80,
                        self::PRUEBA_ACIDA => 0.90,
                        self::ROTACION_INVENTARIO => 6.20,
                        self::DIAS_INVENTARIO => 58.87,
                        self::ROTACION_ACTIVOS => 1.20,
                        self::GRADO_ENDEUDAMIENTO => 45.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.82,
                        self::ROE => 18.00,
                        self::ROA => 10.00,
                        self::CAPITAL_TRABAJO => 300000,
                    ]
                ]
            ],

            // EMPRESA 4: Distribuidora XYZ
            [
                'empresa' => $comercial2,
                'anios' => [
                    2022 => [
                        self::RAZON_CIRCULANTE => 1.70,
                        self::PRUEBA_ACIDA => 0.85,
                        self::ROTACION_INVENTARIO => 5.90,
                        self::DIAS_INVENTARIO => 61.86,
                        self::ROTACION_ACTIVOS => 1.22,
                        self::GRADO_ENDEUDAMIENTO => 44.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.79,
                        self::ROE => 17.50,
                        self::ROA => 9.80,
                        self::CAPITAL_TRABAJO => 280000,
                    ],
                    2023 => [
                        self::RAZON_CIRCULANTE => 1.78,
                        self::PRUEBA_ACIDA => 0.92,
                        self::ROTACION_INVENTARIO => 6.10,
                        self::DIAS_INVENTARIO => 59.84,
                        self::ROTACION_ACTIVOS => 1.25,
                        self::GRADO_ENDEUDAMIENTO => 43.50,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.77,
                        self::ROE => 18.50,
                        self::ROA => 10.20,
                        self::CAPITAL_TRABAJO => 310000,
                    ],
                    2024 => [
                        self::RAZON_CIRCULANTE => 1.85,
                        self::PRUEBA_ACIDA => 0.98,
                        self::ROTACION_INVENTARIO => 6.40,
                        self::DIAS_INVENTARIO => 57.03,
                        self::ROTACION_ACTIVOS => 1.28,
                        self::GRADO_ENDEUDAMIENTO => 43.00,
                        self::ENDEUDAMIENTO_PATRIMONIAL => 0.75,
                        self::ROE => 19.00,
                        self::ROA => 10.50,
                        self::CAPITAL_TRABAJO => 340000,
                    ]
                ]
            ]
        ];

        // 6) Inserción idempotente
        $this->command->info('💾 Insertando ratios calculados...');
        $total = 0;
        foreach ($datosEmpresas as $empresaData) {
            $empresa = $empresaData['empresa'];
            foreach ($empresaData['anios'] as $anio => $ratios) {
                foreach ($ratios as $nombreRatio => $valor) {
                    RatioCalculado::updateOrCreate(
                        [
                            'empresa_id' => $empresa->id,
                            'anio' => $anio,
                            'nombre_ratio' => $nombreRatio,
                        ],
                        [
                            'valor_ratio' => $valor,
                        ]
                    );
                    $total++;
                }
            }
        }

        $this->command->info("✅ {$total} ratios calculados insertados/actualizados");
        $this->command->info('🎉 RatiosCalculadosSeeder completado');
    }
}
