<?php

use App\Services\ProyeccionService;

beforeEach(function () {
    $this->service = new ProyeccionService();

    // Datos históricos de prueba (36 períodos)
    $this->datosHistoricos = [
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
});

describe('Método de Mínimos Cuadrados', function () {
    it('calcula proyecciones correctas usando mínimos cuadrados', function () {
        $proyecciones = $this->service->calcularMinimosCuadrados($this->datosHistoricos);

        // Verificar que retorna 12 períodos
        expect($proyecciones)->toHaveCount(12);

        // Resultados esperados
        $resultadosEsperados = [
            ['x' => 37, 'monto' => 374004.76],
            ['x' => 38, 'monto' => 380833.85],
            ['x' => 39, 'monto' => 387662.93],
            ['x' => 40, 'monto' => 394492.02],
            ['x' => 41, 'monto' => 401321.11],
            ['x' => 42, 'monto' => 408150.19],
            ['x' => 43, 'monto' => 414979.28],
            ['x' => 44, 'monto' => 421808.37],
            ['x' => 45, 'monto' => 428637.45],
            ['x' => 46, 'monto' => 435466.54],
            ['x' => 47, 'monto' => 442295.62],
            ['x' => 48, 'monto' => 449124.71],
        ];

        // Verificar cada proyección
        foreach ($resultadosEsperados as $index => $esperado) {
            expect($proyecciones[$index]['x'])->toBe($esperado['x']);
            // Comparar con tolerancia de 0.01
            expect(abs($proyecciones[$index]['monto'] - $esperado['monto']))->toBeLessThan(0.01);
        }
    });

    it('lanza excepción con menos de 2 períodos', function () {
        $this->service->calcularMinimosCuadrados([
            ['x' => 1, 'y' => 100000.00],
        ]);
    })->throws(InvalidArgumentException::class, 'Se requieren al menos 2 períodos de datos.');

    it('lanza excepción con datos sin formato correcto', function () {
        $this->service->calcularMinimosCuadrados([
            ['x' => 1],
            ['x' => 2, 'y' => 200000.00],
        ]);
    })->throws(InvalidArgumentException::class, "Cada elemento debe contener 'x' e 'y'.");

    it('lanza excepción cuando el denominador es cero', function () {
        // Todos los valores X iguales causa denominador = 0
        $this->service->calcularMinimosCuadrados([
            ['x' => 5, 'y' => 100000.00],
            ['x' => 5, 'y' => 200000.00],
            ['x' => 5, 'y' => 300000.00],
        ]);
    })->throws(RuntimeException::class, 'Denominador cero: datos no válidos para regresión.');
});

describe('Método de Incremento Porcentual', function () {
    it('calcula proyecciones correctas usando incremento porcentual', function () {
        $proyecciones = $this->service->calcularIncrementoPorcentual($this->datosHistoricos);

        // Verificar que retorna 12 períodos
        expect($proyecciones)->toHaveCount(12);

        // Resultados esperados
        $resultadosEsperados = [
            ['x' => 37, 'monto' => 384973.81],
            ['x' => 38, 'monto' => 400553.61],
            ['x' => 39, 'monto' => 416763.92],
            ['x' => 40, 'monto' => 433630.26],
            ['x' => 41, 'monto' => 451179.17],
            ['x' => 42, 'monto' => 469438.28],
            ['x' => 43, 'monto' => 488436.34],
            ['x' => 44, 'monto' => 508203.24],
            ['x' => 45, 'monto' => 528770.11],
            ['x' => 46, 'monto' => 550169.31],
            ['x' => 47, 'monto' => 572434.53],
            ['x' => 48, 'monto' => 595600.82],
        ];

        // Verificar cada proyección
        foreach ($resultadosEsperados as $index => $esperado) {
            expect($proyecciones[$index]['x'])->toBe($esperado['x']);
            // Comparar con tolerancia de 0.01
            expect(abs($proyecciones[$index]['monto'] - $esperado['monto']))->toBeLessThan(0.01);
        }
    });

    it('lanza excepción con menos de 2 períodos', function () {
        $this->service->calcularIncrementoPorcentual([
            ['x' => 1, 'y' => 100000.00],
        ]);
    })->throws(InvalidArgumentException::class, 'Se requieren al menos 2 períodos de datos.');

    it('lanza excepción cuando todos los valores anteriores son cero', function () {
        $this->service->calcularIncrementoPorcentual([
            ['x' => 1, 'y' => 0.00],
            ['x' => 2, 'y' => 0.00],
            ['x' => 3, 'y' => 100000.00],
        ]);
    })->throws(RuntimeException::class, 'No se pudieron calcular variaciones porcentuales. Todos los valores anteriores son cero.');
});

describe('Método de Incremento Absoluto', function () {
    it('calcula proyecciones correctas usando incremento absoluto', function () {
        $proyecciones = $this->service->calcularIncrementoAbsoluto($this->datosHistoricos);

        // Verificar que retorna 12 períodos
        expect($proyecciones)->toHaveCount(12);

        // Resultados esperados
        $resultadosEsperados = [
            ['x' => 37, 'monto' => 377714.29],
            ['x' => 38, 'monto' => 385428.57],
            ['x' => 39, 'monto' => 393142.86],
            ['x' => 40, 'monto' => 400857.14],
            ['x' => 41, 'monto' => 408571.43],
            ['x' => 42, 'monto' => 416285.71],
            ['x' => 43, 'monto' => 424000.00],
            ['x' => 44, 'monto' => 431714.29],
            ['x' => 45, 'monto' => 439428.57],
            ['x' => 46, 'monto' => 447142.86],
            ['x' => 47, 'monto' => 454857.14],
            ['x' => 48, 'monto' => 462571.43],
        ];

        // Verificar cada proyección
        foreach ($resultadosEsperados as $index => $esperado) {
            expect($proyecciones[$index]['x'])->toBe($esperado['x']);
            // Comparar con tolerancia de 0.01
            expect(abs($proyecciones[$index]['monto'] - $esperado['monto']))->toBeLessThan(0.01);
        }
    });

    it('lanza excepción con menos de 2 períodos', function () {
        $this->service->calcularIncrementoAbsoluto([
            ['x' => 1, 'y' => 100000.00],
        ]);
    })->throws(InvalidArgumentException::class, 'Se requieren al menos 2 períodos de datos.');

    it('lanza excepción con array vacío', function () {
        $this->service->calcularIncrementoAbsoluto([]);
    })->throws(InvalidArgumentException::class);
});

describe('Validaciones Generales', function () {
    it('retorna valores no negativos cuando la proyección es negativa', function () {
        // Datos con tendencia muy negativa
        $datosNegativos = [
            ['x' => 1, 'y' => 100000.00],
            ['x' => 2, 'y' => 50000.00],
            ['x' => 3, 'y' => 10000.00],
        ];

        $proyecciones = $this->service->calcularMinimosCuadrados($datosNegativos);

        // Verificar que todos los montos son >= 0
        foreach ($proyecciones as $proyeccion) {
            expect($proyeccion['monto'])->toBeGreaterThanOrEqual(0);
        }
    });

    it('mantiene el formato correcto de salida', function () {
        $proyecciones = $this->service->calcularMinimosCuadrados($this->datosHistoricos);

        foreach ($proyecciones as $proyeccion) {
            expect($proyeccion)->toHaveKeys(['x', 'monto']);
            expect($proyeccion['x'])->toBeInt();
            expect($proyeccion['monto'])->toBeFloat();
        }
    });

    it('genera secuencia correcta de valores X', function () {
        $proyecciones = $this->service->calcularMinimosCuadrados($this->datosHistoricos);

        // Verificar que X comienza en 37 y termina en 48
        expect($proyecciones[0]['x'])->toBe(37);
        expect($proyecciones[11]['x'])->toBe(48);

        // Verificar secuencia consecutiva
        for ($i = 0; $i < 11; $i++) {
            expect($proyecciones[$i + 1]['x'])->toBe($proyecciones[$i]['x'] + 1);
        }
    });
});
