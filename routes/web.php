<?php

use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\CatalogosCuentasController;
use App\Http\Controllers\CuentasBaseController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\EstadosFinancierosController;
use App\Http\Controllers\ProyeccionesVentasController;
use App\Http\Controllers\RatiosController;
use App\Http\Controllers\SectoresController;
use App\Http\Controllers\Administracion\PlantillaCatalogoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\Administracion\ImportacionCuentasBaseController;
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
        Route::resource('sectores.ratios', RatiosController::class)->shallow()->middleware('can:ratios.index');
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

    // Análisis (Accesible para todos los roles con permisos de lectura)
    Route::prefix('analisis/{empresa}')->name('analisis.')->middleware('can:informes.index')->group(function () {
        Route::get('ratios/{anio}', [AnalisisController::class, 'obtenerComparacionRatios'])->name('ratios');
        Route::get('horizontal', [AnalisisController::class, 'obtenerAnalisisHorizontal'])->name('horizontal');
        Route::get('historial-cuenta', [AnalisisController::class, 'obtenerHistorialCuenta'])->name('historial-cuenta');
    });

    // Proyecciones (Para 'Gerente Financiero' y 'Administrador')
    Route::resource('empresas.proyecciones', ProyeccionesVentasController::class)->shallow()->middleware('can:proyecciones.index');

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
            ->name('importacion.plantilla');
        Route::get('documentacion', [ImportacionController::class, 'documentacion'])
            ->name('importacion.documentacion');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';