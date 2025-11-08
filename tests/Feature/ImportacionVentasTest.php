<?php

use App\Models\DatoVentaHistorico;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

beforeEach(function () {
    // Ejecutar seeders para crear roles y permisos
    seed(RolesAndPermissionsSeeder::class);

    // Crear un usuario con permisos de proyecciones
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['proyecciones.index', 'proyecciones.create']);

    // Crear sector necesario para empresa
    $this->sector = Sector::create([
        'nombre' => 'Sector Test',
        'descripcion' => 'Sector de prueba',
    ]);

    // Crear una empresa de prueba
    $this->empresa = Empresa::create([
        'nombre' => 'Empresa Test',
        'sector_id' => $this->sector->id,
        'usuario_id' => $this->user->id,
    ]);

    // Configurar storage falso
    Storage::fake('local');
});

describe('Importación CSV - Camino Feliz', function () {
    test('importación csv con datos continuos es exitosa', function () {
        // Crear contenido CSV válido con 12 meses
        $csvContent = "Anio;Mes;Monto_Venta\n";
        for ($mes = 1; $mes <= 12; $mes++) {
            $csvContent .= "2024;{$mes};" . (100000 + ($mes * 5000)) . "\n";
        }

        // Crear archivo CSV temporal
        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación como usuario autenticado
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar redirección exitosa
        $response->assertRedirect(route('dashboard.proyecciones', $this->empresa->id));
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        // Verificar que se insertaron 12 registros
        assertDatabaseCount('datos_venta_historicos', 12);

        // Verificar algunos registros específicos
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 105000,
        ]);

        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 12,
            'monto' => 160000,
        ]);

        // Verificar mensaje de éxito
        expect(session('success'))->toContain('12 filas insertadas');
    });

    test('importación csv actualiza registros existentes (lógica upsert)', function () {
        // Crear un dato existente en la BD
        DatoVentaHistorico::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 1000.00,
        ]);

        // Verificar que existe
        assertDatabaseCount('datos_venta_historicos', 1);

        // Crear CSV que actualiza Ene y agrega Feb
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;5000.00\n";  // Actualizar Enero
        $csvContent .= "2024;2;2000.00\n";  // Insertar Febrero

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect(route('dashboard.proyecciones', $this->empresa->id));
        $response->assertSessionHas('success');

        // Verificar que solo hay 2 registros (no 3)
        assertDatabaseCount('datos_venta_historicos', 2);

        // Verificar que Enero fue actualizado
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 5000.00,
        ]);

        // Verificar que Febrero fue insertado
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 2,
            'monto' => 2000.00,
        ]);

        // Verificar mensaje de éxito con contadores
        expect(session('success'))->toContain('1 filas insertadas');
        expect(session('success'))->toContain('1 filas actualizadas');
    });

    test('importación csv permite actualizar múltiples registros sin errores', function () {
        // Crear 6 meses de datos históricos
        for ($mes = 1; $mes <= 6; $mes++) {
            DatoVentaHistorico::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2024,
                'mes' => $mes,
                'monto' => 10000 * $mes,
            ]);
        }

        // CSV que actualiza los 6 meses existentes
        $csvContent = "Anio;Mes;Monto_Venta\n";
        for ($mes = 1; $mes <= 6; $mes++) {
            $csvContent .= "2024;{$mes};" . (20000 * $mes) . "\n";
        }

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que sigue habiendo 6 registros
        assertDatabaseCount('datos_venta_historicos', 6);

        // Verificar que todos fueron actualizados
        for ($mes = 1; $mes <= 6; $mes++) {
            assertDatabaseHas('datos_venta_historicos', [
                'empresa_id' => $this->empresa->id,
                'anio' => 2024,
                'mes' => $mes,
                'monto' => 20000 * $mes,
            ]);
        }

        // Verificar mensaje
        expect(session('success'))->toContain('0 filas insertadas');
        expect(session('success'))->toContain('6 filas actualizadas');
    });

    test('importación csv con datos desordenados los ordena correctamente', function () {
        // CSV con datos desordenados
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;3;30000\n";
        $csvContent .= "2024;1;10000\n";
        $csvContent .= "2024;2;20000\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que todos los registros se insertaron correctamente
        assertDatabaseCount('datos_venta_historicos', 3);

        // Verificar que los datos están en la BD independientemente del orden en el CSV
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 10000,
        ]);

        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 2,
            'monto' => 20000,
        ]);

        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 3,
            'monto' => 30000,
        ]);
    });

    test('importación csv acepta formato decimal con coma', function () {
        // CSV con montos usando coma como separador decimal
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;150000,50\n";
        $csvContent .= "2024;2;165000,75\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que los montos se guardaron correctamente
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 150000.50,
        ]);

        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 2,
            'monto' => 165000.75,
        ]);
    });
});

describe('Importación CSV - Errores de Sintaxis (Fase 1)', function () {
    test('importación falla si el archivo no es csv', function () {
        // Crear un archivo PNG falso
        $pngFile = UploadedFile::fake()->image('test.png');

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $pngFile,
            ]);

        // Verificar que hay error de validación
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla si no se proporciona archivo', function () {
        // Intentar importar sin archivo
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), []);

        // Verificar error de validación
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con cabeceras incorrectas', function () {
        // CSV con cabeceras incorrectas
        $csvContent = "Year;Month;Amount\n";
        $csvContent .= "2024;1;10000\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        // Verificar mensaje de error específico
        $errors = session('errors');
        expect($errors->get('csv_file')[0])
            ->toContain('cabeceras del archivo no son válidas');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con errores de formato de fila (mes inválido)', function () {
        // CSV con mes inválido (13)
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;10000\n";
        $csvContent .= "2024;13;20000\n";  // Mes inválido
        $csvContent .= "2024;3;30000\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors(['csv_file', 'errores_fila']);

        // Verificar que el error específico está en la lista
        $erroresFila = session('errors')->get('errores_fila');
        expect($erroresFila)->toBeArray();
        expect($erroresFila[0]['fila'])->toBe(2);
        expect($erroresFila[0]['error'])->toContain('Mes inválido');

        // Verificar que no se insertó nada (rollback por errores de sintaxis)
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con año inválido', function () {
        // CSV con año inválido
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "202a;1;10000\n";  // Año inválido

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        // Verificar mensaje de error específico
        $erroresFila = session('errors')->get('errores_fila');
        expect($erroresFila[0]['error'])->toContain('Año inválido');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con monto negativo', function () {
        // CSV con monto negativo
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;-10000\n";  // Monto negativo

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        // Verificar mensaje de error específico
        $erroresFila = session('errors')->get('errores_fila');
        expect($erroresFila[0]['error'])->toContain('Monto inválido');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con monto no numérico', function () {
        // CSV con monto no numérico
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;ABC123\n";  // Monto no numérico

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        // Verificar mensaje de error
        $erroresFila = session('errors')->get('errores_fila');
        expect($erroresFila[0]['error'])->toContain('Monto inválido');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con número incorrecto de columnas', function () {
        // CSV con columnas faltantes
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1\n";  // Falta la columna del monto

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        // Verificar mensaje de error
        $erroresFila = session('errors')->get('errores_fila');
        expect($erroresFila[0]['error'])->toContain('exactamente 3 columnas');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla con duplicados dentro del csv', function () {
        // CSV con período duplicado
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;10000\n";
        $csvContent .= "2024;2;20000\n";
        $csvContent .= "2024;1;15000\n";  // Duplicado de Enero 2024

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        // Verificar mensaje de error específico
        $erroresFila = session('errors')->get('errores_fila');
        $errorDuplicado = collect($erroresFila)->first(function ($error) {
            return str_contains($error['error'], 'duplicado');
        });

        expect($errorDuplicado)->not->toBeNull();
        expect($errorDuplicado['error'])->toContain('Período duplicado');
        expect($errorDuplicado['error'])->toContain('Enero 2024');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla si el archivo excede 1000 filas', function () {
        // Crear CSV con más de 1000 filas
        $csvContent = "Anio;Mes;Monto_Venta\n";
        for ($i = 1; $i <= 1001; $i++) {
            $anio = 2000 + floor($i / 12);
            $mes = ($i % 12) + 1;
            $csvContent .= "{$anio};{$mes};10000\n";
        }

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        // Verificar mensaje de error
        $errors = session('errors');
        expect($errors->get('csv_file')[0])->toContain('excede el límite de 1000 filas');

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación ignora filas completamente vacías', function () {
        // CSV con filas vacías intercaladas
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;10000\n";
        $csvContent .= ";;\n";  // Fila vacía
        $csvContent .= "2024;2;20000\n";
        $csvContent .= "\n";  // Fila vacía
        $csvContent .= "2024;3;30000\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que solo se insertaron 3 registros (las filas vacías fueron ignoradas)
        assertDatabaseCount('datos_venta_historicos', 3);
    });
});

describe('Importación CSV - Errores de Lógica (Fase 2)', function () {
    test('importación falla al crear un vacío cronológico', function () {
        // Crear dato existente en BD para Enero 2024
        DatoVentaHistorico::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 10000,
        ]);

        // CSV que intenta insertar Marzo (saltando Febrero)
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;3;30000\n";  // Debería ser Febrero 2024

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        // Verificar mensaje de error de continuidad
        $errors = session('errors');
        expect($errors->get('csv_file')[0])->toContain('Error de continuidad');
        expect($errors->get('csv_file')[0])->toContain('se esperaba Febrero 2024');
        expect($errors->get('csv_file')[0])->toContain('se encontró Marzo 2024');

        // Verificar rollback: solo debe existir el registro original
        assertDatabaseCount('datos_venta_historicos', 1);
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 10000,
        ]);
    });

    test('importación falla con vacío cronológico después de año nuevo', function () {
        // Crear dato existente para Diciembre 2023
        DatoVentaHistorico::create([
            'empresa_id' => $this->empresa->id,
            'anio' => 2023,
            'mes' => 12,
            'monto' => 50000,
        ]);

        // CSV que intenta insertar Febrero 2024 (saltando Enero 2024)
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;2;60000\n";  // Debería ser Enero 2024

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error de continuidad
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        $errors = session('errors');
        expect($errors->get('csv_file')[0])->toContain('Error de continuidad');
        expect($errors->get('csv_file')[0])->toContain('se esperaba Enero 2024');

        // Verificar rollback
        assertDatabaseCount('datos_venta_historicos', 1);
    });

    test('importación falla con vacío en medio de múltiples inserciones', function () {
        // CSV ordenado con un vacío en el medio
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2024;1;10000\n";
        $csvContent .= "2024;2;20000\n";
        $csvContent .= "2024;4;40000\n";  // Falta el mes 3
        $csvContent .= "2024;5;50000\n";

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Intentar importar
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar error
        $response->assertRedirect();
        $response->assertSessionHasErrors('csv_file');

        $errors = session('errors');
        expect($errors->get('csv_file')[0])->toContain('Error de continuidad');
        expect($errors->get('csv_file')[0])->toContain('fila 3'); // La fila del CSV donde está el error

        // Verificar rollback completo (nada se insertó)
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación exitosa cuando csv completa los vacíos existentes correctamente', function () {
        // Crear datos existentes: Ene, Feb, Mar 2024
        for ($mes = 1; $mes <= 3; $mes++) {
            DatoVentaHistorico::create([
                'empresa_id' => $this->empresa->id,
                'anio' => 2024,
                'mes' => $mes,
                'monto' => 10000 * $mes,
            ]);
        }

        // CSV que continúa correctamente desde Abril
        $csvContent = "Anio;Mes;Monto_Venta\n";
        for ($mes = 4; $mes <= 6; $mes++) {
            $csvContent .= "2024;{$mes};" . (10000 * $mes) . "\n";
        }

        $csvFile = UploadedFile::fake()->createWithContent(
            'datos_ventas.csv',
            $csvContent
        );

        // Ejecutar importación
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que ahora hay 6 registros
        assertDatabaseCount('datos_venta_historicos', 6);

        // Verificar que los nuevos meses están en la BD
        for ($mes = 4; $mes <= 6; $mes++) {
            assertDatabaseHas('datos_venta_historicos', [
                'empresa_id' => $this->empresa->id,
                'anio' => 2024,
                'mes' => $mes,
                'monto' => 10000 * $mes,
            ]);
        }
    });
});

describe('Importación CSV - Autorización y Seguridad', function () {
    test('importación falla sin autenticación', function () {
        // CSV válido
        $csvContent = "Anio;Mes;Monto_Venta\n2024;1;10000\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        // Intentar importar sin autenticación
        $response = $this->post(route('proyecciones.importar-csv', $this->empresa->id), [
            'csv_file' => $csvFile,
        ]);

        // Verificar redirección al login
        $response->assertRedirect(route('login'));

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación falla sin permisos adecuados', function () {
        // Crear usuario sin permisos de proyecciones
        $userSinPermisos = User::factory()->create();

        // CSV válido
        $csvContent = "Anio;Mes;Monto_Venta\n2024;1;10000\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        // Intentar importar sin permisos
        $response = actingAs($userSinPermisos)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar respuesta 403 Forbidden
        $response->assertForbidden();

        // Verificar que no se insertó nada
        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación solo afecta a la empresa especificada', function () {
        // Crear segunda empresa
        $empresa2 = Empresa::create([
            'nombre' => 'Empresa 2',
            'sector_id' => $this->sector->id,
            'usuario_id' => $this->user->id,
        ]);

        // Crear datos para la segunda empresa
        DatoVentaHistorico::create([
            'empresa_id' => $empresa2->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 99999,
        ]);

        // CSV para la primera empresa
        $csvContent = "Anio;Mes;Monto_Venta\n2024;1;10000\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        // Importar para empresa 1
        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        // Verificar éxito
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que hay 2 registros totales (1 por empresa)
        assertDatabaseCount('datos_venta_historicos', 2);

        // Verificar que los datos de empresa 2 no fueron afectados
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $empresa2->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 99999,
        ]);

        // Verificar que empresa 1 tiene su propio dato
        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 10000,
        ]);
    });
});

describe('Importación CSV - Casos Edge', function () {
    test('importación exitosa con monto cero', function () {
        $csvContent = "Anio;Mes;Monto_Venta\n2024;1;0\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 0,
        ]);
    });

    test('importación maneja correctamente montos muy grandes', function () {
        $csvContent = "Anio;Mes;Monto_Venta\n2024;1;999999999.99\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        assertDatabaseHas('datos_venta_historicos', [
            'empresa_id' => $this->empresa->id,
            'anio' => 2024,
            'mes' => 1,
            'monto' => 999999999.99,
        ]);
    });

    test('importación maneja años límite correctamente', function () {
        $csvContent = "Anio;Mes;Monto_Venta\n";
        $csvContent .= "2000;1;10000\n";
        $csvContent .= "2000;2;20000\n";

        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        assertDatabaseCount('datos_venta_historicos', 2);
    });

    test('importación rechaza año fuera de rango inferior', function () {
        $csvContent = "Anio;Mes;Monto_Venta\n1999;1;10000\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        assertDatabaseCount('datos_venta_historicos', 0);
    });

    test('importación rechaza año fuera de rango superior', function () {
        $csvContent = "Anio;Mes;Monto_Venta\n2101;1;10000\n";
        $csvFile = UploadedFile::fake()->createWithContent('datos.csv', $csvContent);

        $response = actingAs($this->user)
            ->post(route('proyecciones.importar-csv', $this->empresa->id), [
                'csv_file' => $csvFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('errores_fila');

        assertDatabaseCount('datos_venta_historicos', 0);
    });
});
