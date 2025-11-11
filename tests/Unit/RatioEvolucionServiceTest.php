<?php

/**
 * Tests para RatioEvolucionService
 *
 * Objetivo: Estos tests son la fuente de verdad.
 * Si un test falla, es porque hay un error en el servicio.
 */

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Models\RatioSector;
use App\Models\Sector;
use App\Services\RatioEvolucionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RatioEvolucionService();

    // Crear sector de prueba
    $this->sector = Sector::create([
        'nombre' => 'Sector Tecnología',
        'descripcion' => 'Sector de prueba',
    ]);

    // Crear empresa de prueba
    $this->empresa = Empresa::create([
        'nombre' => 'Tech Innovations S.A.',
        'sector_id' => $this->sector->id,
        'ruc' => '1234567890001',
    ]);
});

describe('obtenerEvolucionCompleta - Casos básicos', function () {

    it('retorna array vacío cuando no hay ratios históricos', function () {
        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        expect($resultado)->toBeArray();
        expect($resultado)->toBeEmpty();
    });

    it('retorna evolución completa cuando hay datos de un solo año', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        expect($resultado)->toHaveKeys(['empresa', 'anios_disponibles', 'ratios']);
        expect($resultado['empresa']['nombre'])->toBe('Tech Innovations S.A.');
        expect($resultado['anios_disponibles'])->toBe([2023]);
    });

    it('retorna evolución con múltiples años ordenados', function () {
        $anios = [2021, 2023, 2020, 2022]; // Desordenados

        foreach ($anios as $anio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => $anio,
                'nombre_ratio' => 'roe',
                'valor_ratio' => 10.0 + $anio - 2020,
            ]);
        }

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        expect($resultado['anios_disponibles'])->toBe([2020, 2021, 2022, 2023]);
    });

    it('incluye información correcta de la empresa', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        expect($resultado['empresa'])->toHaveKeys(['id', 'nombre', 'sector']);
        expect($resultado['empresa']['id'])->toBe($this->empresa->id);
        expect($resultado['empresa']['sector'])->toBe('Sector Tecnología');
    });
});

describe('obtenerEvolucionCompleta - Estructura de ratios', function () {

    it('incluye todos los ratios que tienen datos', function () {
        $ratios = ['roe', 'roa', 'razon_circulante'];

        foreach ($ratios as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 10.0,
            ]);
        }

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        expect($resultado['ratios'])->toHaveKeys(['roe', 'roa', 'razon_circulante']);
    });

    it('no incluye ratios sin datos aunque estén definidos', function () {
        // Solo crear ROE
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        // Solo debe incluir ROE, no los otros 9 ratios
        expect($resultado['ratios'])->toHaveKey('roe');
        expect($resultado['ratios'])->not->toHaveKey('roa');
        expect($resultado['ratios'])->not->toHaveKey('razon_circulante');
    });
});

describe('obtenerEvolucionRatio - Casos básicos', function () {

    it('retorna array vacío para ratio sin datos', function () {
        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado)->toBeArray();
        expect($resultado)->toBeEmpty();
    });

    it('retorna array vacío para ratio no válido', function () {
        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'ratio_inventado',
            $this->sector->id
        );

        expect($resultado)->toBeEmpty();
    });

    it('retorna evolución correcta con un solo punto de datos', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.25,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado)->toHaveKeys([
            'nombre_ratio',
            'clave_ratio',
            'formula',
            'categoria',
            'serie_empresa',
            'benchmark_sector',
            'promedios_sector',
            'tendencia'
        ]);

        expect($resultado['serie_empresa'])->toHaveCount(1);
        expect($resultado['serie_empresa'][0]['anio'])->toBe(2023);
        expect($resultado['serie_empresa'][0]['valor'])->toBe(15.25);
    });

    it('obtiene sector_id automáticamente si no se proporciona', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // No pasar sector_id (debe obtenerlo de la empresa)
        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe'
        );

        expect($resultado)->not->toBeEmpty();
    });
});

describe('obtenerEvolucionRatio - Serie temporal', function () {

    it('ordena los datos por año ascendente', function () {
        $anios = [2023, 2020, 2022, 2021]; // Desordenados

        foreach ($anios as $anio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => $anio,
                'nombre_ratio' => 'roe',
                'valor_ratio' => $anio * 0.1,
            ]);
        }

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        $serie = $resultado['serie_empresa'];
        expect($serie[0]['anio'])->toBe(2020);
        expect($serie[1]['anio'])->toBe(2021);
        expect($serie[2]['anio'])->toBe(2022);
        expect($serie[3]['anio'])->toBe(2023);
    });

    it('convierte valores a float correctamente', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => '15.25', // String
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['serie_empresa'][0]['valor'])->toBeFloat();
        expect($resultado['serie_empresa'][0]['valor'])->toBe(15.25);
    });

    it('maneja correctamente años no consecutivos', function () {
        // 2020, 2023, 2025 (saltos)
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2025,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 20.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['serie_empresa'])->toHaveCount(3);
        expect($resultado['serie_empresa'][0]['anio'])->toBe(2020);
        expect($resultado['serie_empresa'][1]['anio'])->toBe(2023);
        expect($resultado['serie_empresa'][2]['anio'])->toBe(2025);
    });
});

describe('obtenerEvolucionRatio - Benchmark del sector', function () {

    it('incluye benchmark cuando existe', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.5,
            'fuente' => 'Superintendencia de Compañías',
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['benchmark_sector'])->not->toBeNull();
        expect($resultado['benchmark_sector']['valor'])->toBe(12.5);
        expect($resultado['benchmark_sector']['fuente'])->toBe('Superintendencia de Compañías');
    });

    it('retorna null para benchmark cuando no existe', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // No crear benchmark

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['benchmark_sector'])->toBeNull();
    });

    it('convierte benchmark a float', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => '12.5000', // String con decimales
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['benchmark_sector']['valor'])->toBeFloat();
    });
});

describe('obtenerEvolucionRatio - Promedios del sector por año', function () {

    it('calcula promedios correctamente con múltiples empresas', function () {
        // Empresa principal
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // Empresa 2
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 9.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['promedios_sector'])->toHaveCount(1);
        expect($resultado['promedios_sector'][0]['anio'])->toBe(2023);
        expect($resultado['promedios_sector'][0]['valor'])->toBe(12.0); // (15+9)/2
    });

    it('calcula promedios solo para los años que tienen datos', function () {
        // Empresa principal: 2020, 2022
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2022,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 14.0,
        ]);

        // Empresa 2: solo 2020
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 8.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['promedios_sector'])->toHaveCount(2);

        // 2020: promedio de 10 y 8 = 9
        expect($resultado['promedios_sector'][0]['anio'])->toBe(2020);
        expect($resultado['promedios_sector'][0]['valor'])->toBe(9.0);

        // 2022: solo empresa principal = 14
        expect($resultado['promedios_sector'][1]['anio'])->toBe(2022);
        expect($resultado['promedios_sector'][1]['valor'])->toBe(14.0);
    });

    it('excluye empresas de otros sectores del promedio', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // Empresa de otro sector
        $otroSector = Sector::create([
            'nombre' => 'Otro Sector',
            'descripcion' => 'Otro',
        ]);

        $empresaOtroSector = Empresa::create([
            'nombre' => 'Empresa Otro Sector',
            'sector_id' => $otroSector->id,
            'ruc' => '9999999999001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresaOtroSector->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 999.0, // No debe incluirse
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        // Promedio debe ser solo 15.0, no incluir 999.0
        expect($resultado['promedios_sector'][0]['valor'])->toBe(15.0);
    });

    it('redondea promedios a 4 decimales', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.123456,
        ]);

        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.987654,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        $promedio = $resultado['promedios_sector'][0]['valor'];
        expect(strlen(substr(strrchr($promedio, "."), 1)))->toBeLessThanOrEqual(4);
    });

    it('retorna array vacío de promedios cuando no hay años', function () {
        // No crear ratios

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado)->toBeEmpty();
    });
});

describe('obtenerEvolucionRatio - Cálculo de tendencia', function () {

    it('determina tendencia ascendente cuando hay crecimiento > 5%', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0, // 50% de crecimiento
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['tendencia']['direccion'])->toBe('ascendente');
        expect($resultado['tendencia']['variacion_porcentual'])->toBe(50.0);
        expect($resultado['tendencia']['valor_inicial'])->toBe(10.0);
        expect($resultado['tendencia']['valor_final'])->toBe(15.0);
    });

    it('determina tendencia descendente cuando hay caída > 5%', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 20.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0, // -25% de caída
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['tendencia']['direccion'])->toBe('descendente');
        expect($resultado['tendencia']['variacion_porcentual'])->toBe(-25.0);
    });

    it('determina tendencia estable cuando variación < 5%', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.5, // 3.33% de variación
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['tendencia']['direccion'])->toBe('estable');
    });

    it('retorna sin_datos cuando hay menos de 2 puntos', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['tendencia']['direccion'])->toBe('sin_datos');
        expect($resultado['tendencia']['variacion'])->toBe(0);
    });

    it('maneja correctamente cuando valor inicial es cero', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 0.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        // No debe lanzar error de división por cero
        expect($resultado['tendencia']['variacion_porcentual'])->toBe(0.0);
    });

    it('redondea variación porcentual a 2 decimales', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 13.333333,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 17.777777,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        $variacion = $resultado['tendencia']['variacion_porcentual'];
        expect(strlen(substr(strrchr($variacion, "."), 1)))->toBeLessThanOrEqual(2);
    });
});

describe('obtenerEvolucionRatio - Metadatos del ratio', function () {

    it('incluye nombre amigable del ratio', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['nombre_ratio'])->toContain('ROE');
        expect($resultado['nombre_ratio'])->toContain('Rentabilidad');
    });

    it('incluye fórmula del ratio', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['formula'])->toContain('Utilidad');
        expect($resultado['formula'])->toContain('Patrimonio');
    });

    it('categoriza correctamente cada tipo de ratio', function () {
        $ratiosYCategorias = [
            'roe' => 'Rentabilidad',
            'roa' => 'Rentabilidad',
            'razon_circulante' => 'Liquidez',
            'prueba_acida' => 'Liquidez',
            'grado_endeudamiento' => 'Endeudamiento',
            'rotacion_inventario' => 'Actividad',
        ];

        foreach ($ratiosYCategorias as $ratio => $categoriaEsperada) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 10.0,
            ]);

            $resultado = $this->service->obtenerEvolucionRatio(
                $this->empresa->id,
                $ratio,
                $this->sector->id
            );

            expect($resultado['categoria'])->toBe($categoriaEsperada);
        }
    });
});

describe('obtenerEvolucionRatio - Casos extremos', function () {

    it('maneja correctamente valores negativos', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => -5.0, // Pérdidas
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['serie_empresa'][0]['valor'])->toBe(-5.0);
    });

    it('maneja correctamente valores muy grandes', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'rotacion_inventario',
            'valor_ratio' => 999999.9999,
        ]);

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'rotacion_inventario',
            $this->sector->id
        );

        expect($resultado)->not->toBeEmpty();
    });

    it('maneja correctamente período largo de años', function () {
        // 20 años de datos
        for ($anio = 2000; $anio <= 2020; $anio++) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => $anio,
                'nombre_ratio' => 'roe',
                'valor_ratio' => 10.0 + ($anio - 2000) * 0.5,
            ]);
        }

        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            $this->sector->id
        );

        expect($resultado['serie_empresa'])->toHaveCount(21);
        expect($resultado['promedios_sector'])->toHaveCount(21);
    });
});

describe('obtenerEvolucionCompleta - Integración completa', function () {

    it('procesa correctamente múltiples ratios con diferentes años', function () {
        // ROE en 2020, 2021, 2023
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2020,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2021,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // ROA solo en 2023
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roa',
            'valor_ratio' => 8.0,
        ]);

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        expect($resultado['anios_disponibles'])->toBe([2020, 2021, 2023]);
        expect($resultado['ratios'])->toHaveKeys(['roe', 'roa']);

        // ROE debe tener 3 puntos
        expect($resultado['ratios']['roe']['serie_empresa'])->toHaveCount(3);

        // ROA debe tener 1 punto
        expect($resultado['ratios']['roa']['serie_empresa'])->toHaveCount(1);
    });

    it('incluye benchmarks y promedios para todos los ratios', function () {
        $ratios = ['roe', 'roa'];

        foreach ($ratios as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 10.0,
            ]);

            RatioSector::create([
                'sector_id' => $this->sector->id,
                'nombre_ratio' => $ratio,
                'valor_referencia' => 12.0,
                'fuente' => 'Test',
            ]);
        }

        $resultado = $this->service->obtenerEvolucionCompleta($this->empresa->id);

        foreach ($ratios as $ratio) {
            expect($resultado['ratios'][$ratio]['benchmark_sector'])->not->toBeNull();
            expect($resultado['ratios'][$ratio]['promedios_sector'])->not->toBeEmpty();
        }
    });
});

describe('obtenerEvolucionRatio - Errores y excepciones', function () {

    it('lanza excepción cuando empresa no existe', function () {
        $this->service->obtenerEvolucionCompleta(99999);
    })->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    it('maneja correctamente sector inexistente', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // Sector ID que no existe
        $resultado = $this->service->obtenerEvolucionRatio(
            $this->empresa->id,
            'roe',
            9999
        );

        // Debe funcionar pero sin benchmarks ni promedios de sector
        expect($resultado)->not->toBeEmpty();
        expect($resultado['benchmark_sector'])->toBeNull();
    });
});

