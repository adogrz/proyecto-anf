<?php

/**
 * Tests para RatioComparacionService
 *
 * Objetivo: Estos tests son la fuente de verdad.
 * Si un test falla, es porque hay un error en el servicio.
 */

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Models\RatioSector;
use App\Models\Sector;
use App\Services\RatioComparacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RatioComparacionService();

    // Crear sector de prueba
    $this->sector = Sector::create([
        'nombre' => 'Sector Tecnología',
        'descripcion' => 'Sector de prueba',
    ]);

    // Crear empresa de prueba
    $this->empresa = Empresa::create([
        'nombre' => 'Tech Corp S.A.',
        'sector_id' => $this->sector->id,
        'ruc' => '1234567890001',
    ]);
});

describe('compararConBenchmark - Casos básicos', function () {

    it('retorna array vacío cuando no hay ratios calculados para la empresa', function () {
        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toBeArray();
        expect($resultado)->toBeEmpty();
    });

    it('retorna comparaciones correctas cuando hay ratios pero sin benchmarks', function () {
        // Crear ratio calculado sin benchmark
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.25,
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['clave_ratio'])->toBe('roe');
        expect($resultado[0]['valor_empresa'])->toBe(15.25);
        expect($resultado[0]['benchmark'])->toBeNull();
    });

    it('retorna comparaciones completas cuando hay ratios con benchmarks', function () {
        // Crear ratio calculado
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.25,
        ]);

        // Crear benchmark
        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.50,
            'fuente' => 'Superintendencia',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['clave_ratio'])->toBe('roe');
        expect($resultado[0]['valor_empresa'])->toBe(15.25);
        expect($resultado[0]['benchmark'])->not->toBeNull();
        expect($resultado[0]['benchmark']['valor'])->toBe(12.50);
        expect($resultado[0]['benchmark']['fuente'])->toBe('Superintendencia');
    });
});

describe('compararConBenchmark - Cálculo de diferencias', function () {

    it('calcula diferencia absoluta correctamente cuando empresa supera benchmark', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['diferencia']['absoluta'])->toBe(3.0);
    });

    it('calcula diferencia absoluta correctamente cuando empresa está por debajo', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 15.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['diferencia']['absoluta'])->toBe(-5.0);
    });

    it('calcula diferencia porcentual correctamente', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0, // 20% más que 12.5
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.5,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['diferencia']['porcentual'])->toBe(20.0);
    });

    it('maneja correctamente cuando benchmark es cero', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 0.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        // No debe lanzar error de división por cero
        expect($resultado[0]['benchmark']['diferencia']['porcentual'])->toBe(0.0);
    });

    it('redondea diferencias a 4 decimales para absoluta y 2 para porcentual', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.123456,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.654321,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        // Verificar redondeo
        $absoluta = $resultado[0]['benchmark']['diferencia']['absoluta'];
        $porcentual = $resultado[0]['benchmark']['diferencia']['porcentual'];

        expect(strlen(substr(strrchr($absoluta, "."), 1)))->toBeLessThanOrEqual(4);
        expect(strlen(substr(strrchr($porcentual, "."), 1)))->toBeLessThanOrEqual(2);
    });
});

describe('compararConBenchmark - Evaluación cumple/no cumple', function () {

    it('evalúa cumple=true cuando ratio "mayor es mejor" supera benchmark', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe', // Mayor es mejor
            'valor_ratio' => 15.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['cumple'])->toBeTrue();
    });

    it('evalúa cumple=false cuando ratio "mayor es mejor" está por debajo', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe', // Mayor es mejor
            'valor_ratio' => 10.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 15.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['cumple'])->toBeFalse();
    });

    it('evalúa cumple=true cuando ratio "menor es mejor" está por debajo', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'grado_endeudamiento', // Menor es mejor
            'valor_ratio' => 0.40,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'grado_endeudamiento',
            'valor_referencia' => 0.50,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['cumple'])->toBeTrue();
    });

    it('evalúa cumple=false cuando ratio "menor es mejor" supera benchmark', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'grado_endeudamiento', // Menor es mejor
            'valor_ratio' => 0.60,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'grado_endeudamiento',
            'valor_referencia' => 0.50,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['cumple'])->toBeFalse();
    });

    it('maneja correctamente los 3 ratios donde menor es mejor', function () {
        $ratiosMenorMejor = [
            'grado_endeudamiento' => [0.40, 0.50],
            'endeudamiento_patrimonial' => [1.0, 1.5],
            'dias_inventario' => [30.0, 45.0],
        ];

        foreach ($ratiosMenorMejor as $ratio => $valores) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => $valores[0], // Menor que benchmark
            ]);

            RatioSector::create([
                'sector_id' => $this->sector->id,
                'nombre_ratio' => $ratio,
                'valor_referencia' => $valores[1],
                'fuente' => 'Test',
            ]);
        }

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(3);

        foreach ($resultado as $comparacion) {
            expect($comparacion['benchmark']['cumple'])->toBeTrue();
        }
    });
});

describe('compararConBenchmark - Estados visuales', function () {

    it('retorna estado "neutral" cuando diferencia es menor a 2%', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.10, // 1% más
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['estado'])->toBe('neutral');
    });

    it('retorna estado "success" cuando ratio mayor es mejor supera 15%', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0, // 25% más que 12
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['estado'])->toBe('success');
    });

    it('retorna estado "danger" cuando ratio mayor es mejor está 15% por debajo', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 10.0, // ~17% menos que 12
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['estado'])->toBe('danger');
    });

    it('invierte estados para ratios donde menor es mejor', function () {
        // Cuando endeudamiento es ALTO (malo), debe ser danger
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'grado_endeudamiento',
            'valor_ratio' => 0.70, // 40% más que 0.5 (malo)
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'grado_endeudamiento',
            'valor_referencia' => 0.50,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['estado'])->toBe('danger');
    });
});

describe('compararConBenchmark - Categorización de ratios', function () {

    it('categoriza correctamente ratios de liquidez', function () {
        $ratiosLiquidez = ['razon_circulante', 'prueba_acida', 'capital_trabajo'];

        foreach ($ratiosLiquidez as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 2.0,
            ]);
        }

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        foreach ($resultado as $comparacion) {
            expect($comparacion['categoria'])->toBe('Liquidez');
        }
    });

    it('categoriza correctamente ratios de rentabilidad', function () {
        $ratiosRentabilidad = ['roe', 'roa'];

        foreach ($ratiosRentabilidad as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 0.15,
            ]);
        }

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        foreach ($resultado as $comparacion) {
            expect($comparacion['categoria'])->toBe('Rentabilidad');
        }
    });

    it('categoriza correctamente ratios de endeudamiento', function () {
        $ratiosEndeudamiento = ['grado_endeudamiento', 'endeudamiento_patrimonial'];

        foreach ($ratiosEndeudamiento as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 0.50,
            ]);
        }

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        foreach ($resultado as $comparacion) {
            expect($comparacion['categoria'])->toBe('Endeudamiento');
        }
    });

    it('categoriza correctamente ratios de actividad', function () {
        $ratiosActividad = ['rotacion_inventario', 'dias_inventario', 'rotacion_activos'];

        foreach ($ratiosActividad as $ratio) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => 5.0,
            ]);
        }

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        foreach ($resultado as $comparacion) {
            expect($comparacion['categoria'])->toBe('Actividad');
        }
    });
});

describe('compararConBenchmark - Interpretaciones', function () {

    it('genera interpretación correcta cuando prácticamente igual', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 12.10,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['interpretacion'])
            ->toContain('Prácticamente igual al benchmark');
    });

    it('genera interpretación positiva cuando supera benchmark en ratio mayor es mejor', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 18.0,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['benchmark']['interpretacion'])
            ->toContain('Supera el benchmark');
    });
});

describe('compararConBenchmark - Múltiples ratios', function () {

    it('procesa correctamente todos los 10 ratios financieros estándar', function () {
        $todosLosRatios = [
            'razon_circulante' => 2.5,
            'prueba_acida' => 1.8,
            'capital_trabajo' => 0.3,
            'rotacion_inventario' => 8.0,
            'dias_inventario' => 45.0,
            'rotacion_activos' => 1.2,
            'grado_endeudamiento' => 0.45,
            'endeudamiento_patrimonial' => 0.82,
            'roe' => 0.15,
            'roa' => 0.08,
        ];

        foreach ($todosLosRatios as $ratio => $valor) {
            RatioCalculado::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2023,
                'nombre_ratio' => $ratio,
                'valor_ratio' => $valor,
            ]);

            RatioSector::create([
                'sector_id' => $this->sector->id,
                'nombre_ratio' => $ratio,
                'valor_referencia' => $valor * 0.9,
                'fuente' => 'Test',
            ]);
        }

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(10);

        // Verificar que todos tienen la estructura correcta
        foreach ($resultado as $comparacion) {
            expect($comparacion)->toHaveKeys([
                'nombre_ratio',
                'clave_ratio',
                'valor_empresa',
                'formula',
                'categoria',
                'benchmark'
            ]);

            expect($comparacion['benchmark'])->toHaveKeys([
                'valor',
                'fuente',
                'diferencia',
                'cumple',
                'estado',
                'interpretacion'
            ]);
        }
    });
});

describe('compararConBenchmark - Casos extremos', function () {

    it('maneja correctamente valores negativos', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => -5.0, // Pérdidas
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => 12.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['valor_empresa'])->toBe(-5.0);
        expect($resultado[0]['benchmark']['cumple'])->toBeFalse();
    });

    it('maneja correctamente valores muy grandes', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'rotacion_inventario',
            'valor_ratio' => 999999.9999,
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'rotacion_inventario',
            'valor_referencia' => 10.0,
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['benchmark'])->not->toBeNull();
    });

    it('maneja correctamente año futuro', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2099,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2099);

        expect($resultado)->toHaveCount(1);
    });

    it('maneja correctamente año muy antiguo', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 1900,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 1900);

        expect($resultado)->toHaveCount(1);
    });
});

describe('compararConBenchmark - Errores y excepciones', function () {

    it('lanza excepción cuando empresa no existe', function () {
        $this->service->compararConBenchmark(99999, 2023);
    })->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    it('retorna comparaciones sin benchmarks cuando el sector no tiene benchmarks definidos', function () {
        // Crear empresa con sector pero sin benchmarks en ratios_sector
        $empresaSinBenchmarks = Empresa::create([
            'nombre' => 'Empresa Sin Benchmarks S.A.',
            'sector_id' => $this->sector->id, // Tiene sector
            'ruc' => '9999999999001',
        ]);

        RatioCalculado::create([
            'empresa_id' => $empresaSinBenchmarks->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        // No crear benchmarks para este sector
        // No debe lanzar excepción, debe retornar con benchmark = null
        $resultado = $this->service->compararConBenchmark($empresaSinBenchmarks->id, 2023);

        expect($resultado)->toHaveCount(1);
        expect($resultado[0]['benchmark'])->toBeNull();
    });
});

describe('compararConBenchmark - Integridad de datos', function () {

    it('convierte correctamente valores a float', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => '15.25', // String
        ]);

        RatioSector::create([
            'sector_id' => $this->sector->id,
            'nombre_ratio' => 'roe',
            'valor_referencia' => '12.50', // String
            'fuente' => 'Test',
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['valor_empresa'])->toBeFloat();
        expect($resultado[0]['benchmark']['valor'])->toBeFloat();
    });

    it('incluye nombre amigable del ratio', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['nombre_ratio'])->toContain('ROE');
        expect($resultado[0]['nombre_ratio'])->toContain('Rentabilidad');
    });

    it('incluye fórmula del ratio', function () {
        RatioCalculado::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'nombre_ratio' => 'roe',
            'valor_ratio' => 15.0,
        ]);

        $resultado = $this->service->compararConBenchmark($this->empresa->id, 2023);

        expect($resultado[0]['formula'])->toContain('Utilidad');
        expect($resultado[0]['formula'])->toContain('Patrimonio');
    });
});

