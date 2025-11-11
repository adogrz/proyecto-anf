<?php

/**
 * Tests para RatioPromedioService
 *
 * Objetivo: Estos tests son la fuente de verdad.
 * Si un test falla, es porque hay un error en el servicio.
 */

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Models\Sector;
use App\Services\RatioPromedioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RatioPromedioService();

    // Crear sector de prueba
    $this->sector = Sector::create([
        'nombre' => 'Sector Comercio',
        'descripcion' => 'Sector de prueba',
    ]);

    // Crear empresa principal
    $this->empresa = Empresa::create([
        'nombre' => 'Empresa Principal S.A.',
        'sector_id' => $this->sector->id,
        'ruc' => '1234567890001',
    ]);
});

describe('compararConPromedioSector - Casos básicos', function () {

    it('retorna array vacío cuando no hay ratios calculados para la empresa', function () {
        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toBeArray();
        expect($resultado)->toBeEmpty();
    });

    it('retorna comparaciones con promedio cuando hay múltiples empresas', function () {
        // Crear 2 empresas más del mismo sector
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2 S.A.',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        $empresa3 = Empresa::create([
            'nombre' => 'Empresa 3 S.A.',
            'sector_id' => $this->sector->id,
            'ruc' => '3333333333001',
        ]);

        // Crear ratios para las 3 empresas
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa3->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 18.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['clave_ratio'])->toBe('roe');
        expect($resultado[0]['valor_empresa'])->toBe(15.0);
        expect($resultado[0]['promedio_sector'])->not->toBeNull();
    });

    it('retorna comparación incluso cuando es la única empresa del sector', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['promedio_sector'])->not->toBeNull();
        expect($resultado[0]['promedio_sector']['cantidad_empresas'])->toBe(1);
        expect($resultado[0]['promedio_sector']['valor'])->toBe(15.0);
    });
});

describe('compararConPromedioSector - Cálculo de promedios', function () {

    it('calcula promedio correctamente con 3 empresas', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        $empresa3 = Empresa::create([
            'nombre' => 'Empresa 3',
            'sector_id' => $this->sector->id,
            'ruc' => '3333333333001',
        ]);

        // Valores: 10, 15, 20 -> Promedio = 15
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa3->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 20.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['valor'])->toBe(15.0);
        expect($resultado[0]['promedio_sector']['cantidad_empresas'])->toBe(3);
    });

    it('incluye valores mínimo y máximo del sector', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        $empresa3 = Empresa::create([
            'nombre' => 'Empresa 3',
            'sector_id' => $this->sector->id,
            'ruc' => '3333333333001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 8.0, // Mínimo
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa3->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 22.0, // Máximo
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['minimo'])->toBe(8.0);
        expect($resultado[0]['promedio_sector']['maximo'])->toBe(22.0);
    });

    it('redondea promedio a 4 decimales', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores que dan promedio con muchos decimales
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.123456,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.987654,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        $promedio = $resultado[0]['promedio_sector']['valor'];
        expect(strlen(substr(strrchr($promedio, "."), 1)))->toBeLessThanOrEqual(4);
    });

    it('excluye empresas de otros sectores del promedio', function () {
        // Crear otro sector y empresa
        $otroSector = Sector::create([
            'nombre' => 'Otro Sector',
            'descripcion' => 'Otro sector',
        ]);

        $empresaOtroSector = Empresa::create([
            'nombre' => 'Empresa Otro Sector',
            'sector_id' => $otroSector->id,
            'ruc' => '9999999999001',
        ]);

        // Ratios de nuestro sector (promedio debería ser 15)
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // Ratio de otro sector (NO debe incluirse)
        RatioCalculado::create([
            'empresa_id' => $empresaOtroSector->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 999.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        // Solo debe contar la empresa del sector correcto
        expect($resultado[0]['promedio_sector']['valor'])->toBe(15.0);
        expect($resultado[0]['promedio_sector']['cantidad_empresas'])->toBe(1);
    });

    it('excluye empresas con ratios de otros años', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Ratio año 2023
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // Ratio año 2022 (NO debe incluirse)
        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2022,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 999.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        // Solo debe contar el ratio del año 2023
        expect($resultado[0]['promedio_sector']['valor'])->toBe(15.0);
        expect($resultado[0]['promedio_sector']['cantidad_empresas'])->toBe(1);
    });
});

describe('compararConPromedioSector - Cálculo de diferencias', function () {

    it('calcula diferencia absoluta correctamente', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 18, 12 -> Promedio = 15
        // Diferencia absoluta para empresa 1: 18 - 15 = 3
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 18.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['diferencia']['absoluta'])->toBe(3.0);
    });

    it('calcula diferencia porcentual correctamente', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 15, 10 -> Promedio = 12.5
        // Diferencia porcentual para empresa 1: (15 - 12.5) / 12.5 * 100 = 20%
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['diferencia']['porcentual'])->toBe(20.0);
    });

    it('maneja correctamente cuando promedio es cero', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 0.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        // No debe lanzar error de división por cero
        expect($resultado[0]['promedio_sector']['diferencia']['porcentual'])->toBe(100.0);
    });
});

describe('compararConPromedioSector - Posición relativa', function () {

    it('determina "En el promedio" cuando diferencia es menor a 5%', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 12.2, 12.0 -> Promedio = 12.1, diferencia ~0.8%
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.2,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['posicion_relativa'])->toBe('En el promedio');
    });

    it('determina "Superior" cuando diferencia está entre 5% y 20%', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 13, 10 -> Promedio = 11.5, diferencia ~13%
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 13.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['posicion_relativa'])->toBe('Superior');
    });

    it('determina "Muy superior" cuando diferencia es mayor a 20%', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 25, 10 -> Promedio = 17.5, diferencia ~43%
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 25.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['posicion_relativa'])->toBe('Muy superior');
    });

    it('determina "Inferior" cuando diferencia está entre -5% y -20%', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 10, 13 -> Promedio = 11.5, diferencia ~-13%
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 13.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['posicion_relativa'])->toBe('Inferior');
    });

    it('determina "Muy inferior" cuando diferencia es menor a -20%', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        // Valores: 10, 25 -> Promedio = 17.5, diferencia ~-43%
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 25.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['posicion_relativa'])->toBe('Muy inferior');
    });
});

describe('compararConPromedioSector - Interpretaciones', function () {

    it('genera interpretación correcta cuando rendimiento similar', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.2,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['interpretacion'])
            ->toContain('similar al promedio del sector');
    });

    it('genera interpretación positiva para ratio mayor es mejor por encima', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 20.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['interpretacion'])
            ->toContain('Buen desempeño');
    });

    it('genera interpretación para ratio menor es mejor cuando está optimizado', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'grado_endeudamiento',
            'valor_ratio' => 0.30, // Mejor (menor)
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'grado_endeudamiento',
            'valor_ratio' => 0.60,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0]['promedio_sector']['interpretacion'])
            ->toContain('favorable');
    });
});

describe('compararConPromedioSector - Múltiples ratios', function () {

    it('procesa correctamente múltiples ratios de la empresa', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        $ratios = ['roe', 'roa', 'razon_circulante'];

        foreach ($ratios as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 15.0,
            ]);

            RatioCalculado::create([
                'empresa_id' => $empresa2->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 12.0,
            ]);
        }

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(3);

        foreach ($resultado as $comparacion) {
            expect($comparacion['promedio_sector']['cantidad_empresas'])->toBe(2);
            expect($comparacion['promedio_sector']['valor'])->toBe(13.5);
        }
    });

    it('solo incluye en promedio las empresas que tienen ese ratio específico', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        $empresa3 = Empresa::create([
            'nombre' => 'Empresa 3',
            'sector_id' => $this->sector->id,
            'ruc' => '3333333333001',
        ]);

        // Todas tienen ROE
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 20.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa3->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 30.0,
        ]);

        // Solo empresa 1 y 2 tienen ROA
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roa',
            'valor_ratio' => 5.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roa',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        // Buscar ROE
        $roe = collect($resultado)->firstWhere('clave_ratio', 'roe');
        expect($roe['promedio_sector']['cantidad_empresas'])->toBe(3);

        // Buscar ROA
        $roa = collect($resultado)->firstWhere('clave_ratio', 'roa');
        expect($roa['promedio_sector']['cantidad_empresas'])->toBe(2);
    });
});

describe('compararConPromedioSector - Categorización', function () {

    it('categoriza correctamente todos los ratios', function () {
        $ratiosPorCategoria = [
            'Liquidez' => ['razon_circulante', 'prueba_acida', 'capital_trabajo'],
            'Rentabilidad' => ['roe', 'roa'],
            'Endeudamiento' => ['grado_endeudamiento', 'endeudamiento_patrimonial'],
            'Actividad' => ['rotacion_inventario', 'dias_inventario', 'rotacion_activos'],
        ];

        foreach ($ratiosPorCategoria as $categoria => $ratios) {
            foreach ($ratios as $ratio) {
                RatioCalculado::create([
                    'empresa_id' => $this->empresa->id,
                    'anio' => 2023,
                    'nombre_ratio' => $ratio,
                    'valor_ratio' => 10.0,
                ]);
            }
        }

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        foreach ($resultado as $comparacion) {
            expect($comparacion['categoria'])->toBeIn([
                'Liquidez',
                'Rentabilidad',
                'Endeudamiento',
                'Actividad'
            ]);
        }
    });
});

describe('compararConPromedioSector - Casos extremos', function () {

    it('maneja correctamente sector con muchas empresas', function () {
        // Agregar ratio a la empresa principal primero
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        // Crear 49 empresas adicionales (50 en total con la principal)
        for ($i = 1; $i <= 49; $i++) {
            $emp = Empresa::create([
                'nombre' => "Empresa {$i}",
                'sector_id' => $this->sector->id,
                'ruc' => str_pad($i, 13, '0', STR_PAD_LEFT),
            ]);

            RatioCalculado::create([
                'empresa_id' => $emp->id,
                'anio' => 2023,
                'nombre_ratio' => 'roe',
                'valor_ratio' => $i * 0.5, // Valores escalonados
            ]);
        }

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['promedio_sector']['cantidad_empresas'])->toBe(50);
    });

    it('maneja correctamente valores negativos', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => -5.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['promedio_sector']['valor'])->toBe(2.5);
    });

    it('maneja correctamente valores muy dispares', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 0.01,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 999.99,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        // Debe calcular sin errores
        expect($resultado[0]['promedio_sector'])->not->toBeNull();
    });
});

describe('compararConPromedioSector - Errores y excepciones', function () {

    it('lanza excepción cuando empresa no existe', function () {
        $this->service->compararConPromedioSector(99999, 2023);
    })->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    it('retorna comparaciones sin promedios cuando es la única empresa con ese ratio', function () {
        // Empresa es la única con ese ratio específico en el sector
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // Crear otra empresa del mismo sector pero sin ese ratio
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '9999999999001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roa', // Diferente ratio
            'valor_ratio' => 10.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['promedio_sector'])->not->toBeNull();
        expect($resultado[0]['promedio_sector']['cantidad_empresas'])->toBe(1);
    });
});

describe('compararConPromedioSector - Estructura de datos', function () {

    it('retorna estructura completa con todos los campos requeridos', function () {
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'ruc' => '2222222222001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.0,
        ]);

        $resultado = $this->service->compararConPromedioSector($this->empresa->id, 2023);

        expect($resultado[0])->toHaveKeys([
            'nombre_ratio',
            'clave_ratio',
            'valor_empresa',
            'formula',
            'categoria',
            'promedio_sector'
        ]);

        expect($resultado[0]['promedio_sector'])->toHaveKeys([
            'valor',
            'cantidad_empresas',
            'minimo',
            'maximo',
            'diferencia',
            'posicion_relativa',
            'interpretacion'
        ]);

        expect($resultado[0]['promedio_sector']['diferencia'])->toHaveKeys([
            'absoluta',
            'porcentual'
        ]);
    });
});

