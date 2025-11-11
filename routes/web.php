<?php

use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\CatalogosCuentasController;
use App\Http\Controllers\CuentasBaseController;
use App\Http\Controllers\DatoVentaHistoricoController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\EstadosFinancierosController;
use App\Http\Controllers\SectoresController;
use App\Http\Controllers\Administracion\PlantillaCatalogoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\ProyeccionVentasController;
use App\Http\Controllers\CalculoRatiosController;
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
    });

    Route::middleware('can:cuentas-base.index')->group(function () {
        Route::resource('cuentas-base', CuentasBaseController::class);
    });

    Route::middleware('can:plantillas-catalogo.index')->group(function () {
        Route::resource('plantillas-catalogo', PlantillaCatalogoController::class);
    });

    // Gestión de Empresas y sus datos (Para 'Gerente Financiero' y 'Administrador')
    Route::resource('empresas', EmpresasController::class)->middleware('can:empresas.index');
    Route::get('empresas/{empresa}/check-catalog-status', [EmpresasController::class, 'checkCatalogStatus'])->name('empresas.checkCatalogStatus');
    Route::resource('empresas.catalogos', CatalogosCuentasController::class)->shallow()->middleware('can:catalogos.index');
    Route::resource('empresas.estados-financieros', EstadosFinancierosController::class)->shallow()->middleware('can:estados-financieros.index');
    
    

    // Análisis (Accesible para todos los roles con permisos de lectura)
    Route::prefix('analisis')->name('analisis.')->middleware('can:informes.index')->group(function () {
        Route::get('ratios/{anio}', [AnalisisController::class, 'obtenerComparacionRatios'])->name('ratios');
        Route::get('{empresa}', [AnalisisController::class, 'obtenerAnalisis'])->name('index');
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
        Route::post('/importacion/previsualizar', [ImportacionController::class, 'previsualizar'])->name('importacion.previsualizar');
        Route::post('/importacion/guardar-estado-financiero', [ImportacionController::class, 'guardarEstadoFinanciero'])->name('importacion.guardarEstadoFinanciero');

    });

   //Calculo de los 10 ratios financieros principales

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('empresas/{empresa}/ratios/{anio}/calcular', [CalculoRatiosController::class, 'calcular'])
        ->name('ratios.calcular')
        ->middleware('can:ratios.create');
});
 
Route::get('empresas/{empresa}/ratios/calcular-todos', [CalculoRatiosController::class, 'calcularTodos'])
    ->name('ratios.calcular.todos')
    ->middleware('can:ratios.create');


});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
