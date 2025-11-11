<?php

use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\AnalisisRatiosController;
use App\Http\Controllers\CatalogosCuentasController;
use App\Http\Controllers\CuentasBaseController;
use App\Http\Controllers\DatoVentaHistoricoController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\EstadosFinancierosController;
use App\Http\Controllers\RatioAlmanaqueController;
use App\Http\Controllers\GraficoVariacionesController;
use App\Http\Controllers\SectoresController;
use App\Http\Controllers\Administracion\PlantillaCatalogoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\Administracion\ImportacionCuentasBaseController;
use App\Http\Controllers\ProyeccionVentasController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Administración (Solo para rol 'Administrador')
    Route::middleware('can:sectores.index')->group(function () {
        Route::resource('sectores', SectoresController::class);
        Route::get('/administracion/sectores/{sector}/ratios', [RatioAlmanaqueController::class, 'edit'])
        ->name('sectores.ratios.edit');
        Route::post('/administracion/sectores/{sector}/ratios/guardar', [RatioAlmanaqueController::class, 'guardar'])
        ->name('sectores.ratios.guardar');
    });

    Route::middleware('can:cuentas-base.index')->group(function () {
        Route::get('cuentas-base/export', [CuentasBaseController::class, 'export'])->name('cuentas-base.export')->middleware('can:cuentas-base.export');
        Route::get('cuentas-base/download-template', [CuentasBaseController::class, 'downloadTemplate'])->name('cuentas-base.download-template');
        Route::resource('cuentas-base', CuentasBaseController::class);
    });

    Route::middleware('can:plantillas-catalogo.index')->group(function () {
        Route::resource('plantillas-catalogo', PlantillaCatalogoController::class);
    });

    Route::prefix('administracion')->name('admin.')->middleware(['auth', 'can:cuentas-base.import'])->group(function () {
        Route::get('/importacion-base', [\App\Http\Controllers\Administracion\ImportacionBaseController::class, 'index'])->name('importacion-base.index');
        Route::post('/importacion-base/preview', [\App\Http\Controllers\Administracion\ImportacionBaseController::class, 'preview'])->name('importacion-base.preview');
        Route::post('/importacion-base/import', [\App\Http\Controllers\Administracion\ImportacionBaseController::class, 'import'])->name('importacion-base.import');

        Route::post('/importacion-cuentas-base/preview', [ImportacionCuentasBaseController::class, 'preview'])->name('importacion-cuentas-base.preview');
        Route::post('/importacion-cuentas-base', [ImportacionCuentasBaseController::class, 'store'])->name('importacion-cuentas-base.store');
    });

    // Gestión de Empresas y sus datos (Para 'Gerente Financiero' y 'Administrador')
    Route::resource('empresas', EmpresasController::class)->middleware('can:empresas.index');
    Route::get('empresas/{empresa}/check-catalog-status', [EmpresasController::class, 'checkCatalogStatus'])->name('empresas.checkCatalogStatus');
    Route::resource('empresas.catalogos', CatalogosCuentasController::class)->shallow()->middleware('can:catalogos.index');
    Route::resource('empresas.estados-financieros', EstadosFinancierosController::class)->shallow()->middleware('can:estados-financieros.index');

    // Análisis de Ratios por Empresa
    Route::prefix('empresas/{empresa}')->name('empresas.')->middleware('can:ratios-financieros.index')->group(function () {
        Route::prefix('analisis/ratios')->name('analisis.ratios.')->group(function () {
            Route::get('/', [AnalisisRatiosController::class, 'dashboard'])
                ->name('dashboard');

            Route::get('benchmark/{anio}', [AnalisisRatiosController::class, 'compararConBenchmark'])
                ->name('benchmark');

            Route::get('promedio-sector/{anio}', [AnalisisRatiosController::class, 'compararConPromedioSector'])
                ->name('promedio-sector');

            Route::get('evolucion', [AnalisisRatiosController::class, 'evolucionRatios'])
                ->name('evolucion');
        });
    });


    // Análisis (Accesible para todos los roles con permisos de lectura)
    Route::prefix('analisis')->name('analisis.')->middleware('can:informes.index')->group(function () {
        Route::get('ratios/{anio}', [AnalisisController::class, 'obtenerComparacionRatios'])->name('ratios');
        Route::get('{empresa}', [AnalisisController::class, 'obtenerAnalisis'])->name('index');
        Route::get('{empresa}/grafico-variaciones', [GraficoVariacionesController::class, 'index'])->name('grafico-variaciones');
        Route::get('historial-cuenta', [AnalisisController::class, 'obtenerHistorialCuenta'])->name('historial-cuenta');
    });

    // Proyecciones (Para 'Gerente Financiero' y 'Administrador')
    Route::middleware('can:proyecciones.index')->group(function () {
        Route::get('/proyecciones/{empresa}', [ProyeccionVentasController::class, 'dashboard'])->name('dashboard.proyecciones');
        Route::get('/proyecciones/{empresa}/generar', [ProyeccionVentasController::class, 'generar'])->name('proyecciones.generar');

        // Gestión de datos históricos (CRUD)
        Route::get('/proyecciones/{empresa}/next-period', [DatoVentaHistoricoController::class, 'getNextPeriod'])->name('proyecciones.next-period');
        Route::post('/proyecciones/{empresa}/datos-historicos', [DatoVentaHistoricoController::class, 'store'])->name('proyecciones.datos-historicos.store');
        Route::put('/proyecciones/{empresa}/datos-historicos/{id}', [DatoVentaHistoricoController::class, 'update'])->name('proyecciones.datos-historicos.update');
        Route::delete('/proyecciones/{empresa}/datos-historicos/{id}', [DatoVentaHistoricoController::class, 'destroy'])->name('proyecciones.datos-historicos.destroy');

        // Importación CSV
        Route::post('/proyecciones/{empresa}/importar-csv', [ProyeccionVentasController::class, 'importarCSV'])
            ->name('proyecciones.importar-csv');

        // Ruta para descargar la plantilla CSV (genérica)
        Route::get('/proyecciones/plantilla/descargar', [ProyeccionVentasController::class, 'descargarPlantilla'])
            ->name('proyecciones.plantilla.descargar');
    });

    Route::middleware('can:estados-financieros.create')->group(function () {
        Route::get('/importacion/wizard', [ImportacionController::class, 'wizard'])->name('importacion.wizard');
        Route::post('/importacion/automap', [ImportacionController::class, 'automap'])->name('importacion.automap');
        Route::post('/importacion/guardar-mapeo', [ImportacionController::class, 'guardarMapeo'])->name('importacion.guardarMapeo');
        Route::post('/importacion/previsualizar-estado-financiero', [ImportacionController::class, 'previsualizarEstadoFinanciero'])->name('importacion.previsualizarEstadoFinanciero');
        Route::post('/importacion/guardar-estado-financiero', [ImportacionController::class, 'guardarEstadoFinanciero'])->name('importacion.guardarEstadoFinanciero');
        Route::post('/importacion/previsualizar-catalogo-base', [ImportacionController::class, 'previsualizarCatalogoBase'])->name('importacion.previsualizarCatalogoBase');
        Route::post('/importacion/importar-catalogo-base', [ImportacionController::class, 'importarCatalogoBase'])->name('importacion.importarCatalogoBase');
        Route::post('/importacion/crear-empresa', [ImportacionController::class, 'crearEmpresa'])
            ->name('importacion.crear-empresa')
            ->middleware(['auth']);
    });
});

Route::middleware(['auth'])->group(function () {
    // Rutas de importación
    Route::prefix('importacion')->group(function () {
        Route::get('plantilla/{tipo?}', [ImportacionController::class, 'descargarPlantilla'])
            ->name('importacion.descargarPlantilla');
        Route::get('documentacion', [ImportacionController::class, 'documentacion'])
            ->name('importacion.documentacion');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
