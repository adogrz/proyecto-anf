<?php

use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\CatalogosCuentasController;
use App\Http\Controllers\CuentasBaseController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\EstadosFinancierosController;
use App\Http\Controllers\RatiosController;
use App\Http\Controllers\SectoresController;
use App\Http\Controllers\Administracion\PlantillaCatalogoController;
use App\Http\Controllers\ImportacionController;
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
        Route::resource('sectores.ratios', RatiosController::class)->shallow()->middleware('can:ratios.index');
    });

    Route::middleware('can:cuentas-base.index')->group(function () {
        Route::resource('cuentas-base', CuentasBaseController::class);
    });

    Route::middleware('can:plantillas-catalogo.index')->group(function () {
        Route::resource('plantillas-catalogo', PlantillaCatalogoController::class);
    });

    // Gestión de Empresas y sus datos (Para 'Gerente Financiero' y 'Administrador')
    Route::resource('empresas', EmpresasController::class)->middleware('can:empresas.index');
    Route::resource('empresas.catalogos', CatalogosCuentasController::class)->shallow()->middleware('can:catalogos.index');
    Route::resource('empresas.estados-financieros', EstadosFinancierosController::class)->shallow()->middleware('can:estados-financieros.index');

    // Análisis (Accesible para todos los roles con permisos de lectura)
    Route::prefix('analisis/{empresa}')->name('analisis.')->middleware('can:informes.index')->group(function () {
        Route::get('ratios/{anio}', [AnalisisController::class, 'obtenerComparacionRatios'])->name('ratios');
        Route::get('horizontal', [AnalisisController::class, 'obtenerAnalisisHorizontal'])->name('horizontal');
        Route::get('historial-cuenta', [AnalisisController::class, 'obtenerHistorialCuenta'])->name('historial-cuenta');
    });

    // Proyecciones (Para 'Gerente Financiero' y 'Administrador')
    Route::middleware('can:proyecciones.index')->group(function () {
        Route::get('/proyecciones/{empresa}', [ProyeccionVentasController::class, 'dashboard'])->name('dashboard.proyecciones');
        Route::post('/proyecciones/{empresa}/generar', [ProyeccionVentasController::class, 'generar'])->name('proyecciones.generar');
    });

    Route::middleware('can:estados-financieros.create')->group(function () {
        Route::get('/importacion/wizard', [ImportacionController::class, 'wizard'])->name('importacion.wizard');
        Route::post('/importacion/automap', [CatalogosCuentasController::class, 'automap'])->name('importacion.automap');
        Route::post('/importacion/guardar-mapeo', [CatalogosCuentasController::class, 'guardarMapeo'])->name('importacion.guardarMapeo');
        Route::post('/importacion/previsualizar', [ImportacionController::class, 'previsualizar'])->name('importacion.previsualizar');
        Route::post('/importacion/guardar-estado-financiero', [ImportacionController::class, 'guardarEstadoFinanciero'])->name('importacion.guardarEstadoFinanciero');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
