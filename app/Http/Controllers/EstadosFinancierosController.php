<?php

namespace App\Http\Controllers;


use App\Http\Requests\StoreEstadoFinancieroRequest;
use App\Http\Requests\UpdateEstadoFinancieroRequest;
use Illuminate\Support\Facades\DB;
use App\Models\DetalleEstado;
use App\Models\CatalogoCuenta;
use App\Models\CuentaBase;
use Illuminate\Http\Request;

use App\Models\EstadoFinanciero;
use App\Models\Empresa;
use Inertia\Inertia;

class EstadosFinancierosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Empresa $empresa)
    {
        $filterAnio = $request->input('anio');
        $filterTipoEstado = $request->input('tipo_estado');

        $estadosFinancierosQuery = $empresa->estadosFinancieros();

        if ($filterAnio) {
            $estadosFinancierosQuery->where('anio', $filterAnio);
        }

        if ($filterTipoEstado) {
            $estadosFinancierosQuery->where('tipo_estado', $filterTipoEstado);
        }

        $estadosFinancieros = $estadosFinancierosQuery->latest()->get();

        // Get all unique years and types for filter options
        $availableAnios = $empresa->estadosFinancieros()->distinct()->pluck('anio')->sortDesc()->values()->toArray();
        $availableTiposEstado = ['balance_general', 'estado_resultados']; // Assuming these are the only two types

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Index', [
            'empresa' => $empresa,
            'estadosFinancieros' => $estadosFinancieros,
            'filters' => [
                'anio' => $filterAnio,
                'tipo_estado' => $filterTipoEstado,
            ],
            'availableAnios' => $availableAnios,
            'availableTiposEstado' => $availableTiposEstado,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Empresa $empresa)
    {
        // Fetch all CatalogoCuentas and eager load their cuentaBase and its parent hierarchy
        $catalogoCuentas = CatalogoCuenta::with(['cuentaBase' => function ($query) {
            $query->with('parentRecursive'); // Assuming a recursive relationship 'parentRecursive' in CuentaBase
        }])->get();

        // Group CatalogoCuentas by their top-level CuentaBase (e.g., Activo, Pasivo)
        $cuentasBaseRaiz = CuentaBase::whereNull('parent_id')->with('childrenRecursive.catalogoCuentas')->get();

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Create', [
            'empresa' => $empresa,
            'cuentasBaseRaiz' => $cuentasBaseRaiz, // Structured accounts for the form
            'catalogoCuentas' => $catalogoCuentas->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'codigo' => $item->codigo,
                    'cuenta_base_id' => $item->cuenta_base_id,
                    'cuenta_base_nombre' => $item->cuentaBase->nombre ?? null,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstadoFinancieroRequest $request, Empresa $empresa)
    {
        // Asegurarse de que el empresa_id de la solicitud coincida con la empresa de la ruta
        if ($request->empresa_id != $empresa->id) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($request, $empresa) {
            $estadoFinanciero = $empresa->estadosFinancieros()->create([
                'anio' => $request->anio,
                'tipo_estado' => $request->tipo_estado,
            ]);

            foreach ($request->detalles as $detalleData) {
                $estadoFinanciero->detalles()->create([
                    'catalogo_cuenta_id' => $detalleData['catalogo_cuenta_id'],
                    'valor' => $detalleData['valor'],
                ]);
            }
        });

        return redirect()->route('empresas.estados-financieros.index', $empresa)
                         ->with('success', 'Estado Financiero creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Empresa $empresa, $estadoFinancieroId)
    {
        $estadoFinanciero = $empresa->estadosFinancieros()->findOrFail($estadoFinancieroId);

        $estadoFinanciero->load([
            'empresa',
            'detalles.catalogoCuenta.cuentaBase' => function ($query) {
                $query->select('id', 'nombre', 'codigo', 'naturaleza', 'parent_id');
            },
            'detalles.catalogoCuenta.cuentaBase.parentRecursive' // Ensure parent hierarchy is loaded
        ]);

        // Map detalles to ensure all necessary data is present and structured
        $mappedDetalles = $estadoFinanciero->detalles->map(function ($detalle) {
            $catalogoCuenta = $detalle->catalogoCuenta;
            $cuentaBase = $catalogoCuenta ? $catalogoCuenta->cuentaBase : null;
            $rootCuentaBase = null;

            if ($cuentaBase) {
                $rootCuentaBase = $cuentaBase;
                while ($rootCuentaBase->parent_id && $rootCuentaBase->parent) {
                    $rootCuentaBase = $rootCuentaBase->parent;
                }
            }

            return [
                'id' => $detalle->id,
                'valor' => $detalle->valor,
                'catalogo_cuenta' => $catalogoCuenta ? [
                    'id' => $catalogoCuenta->id,
                    'codigo_cuenta' => $catalogoCuenta->codigo_cuenta,
                    'nombre_cuenta' => $catalogoCuenta->nombre_cuenta,
                    'cuenta_base' => $cuentaBase ? [
                        'id' => $cuentaBase->id,
                        'nombre' => $cuentaBase->nombre,
                        'codigo' => $cuentaBase->codigo,
                        'naturaleza' => $cuentaBase->naturaleza,
                        'parent_id' => $cuentaBase->parent_id,
                    ] : null,
                ] : null,
                'root_cuenta_base_name' => $rootCuentaBase ? $rootCuentaBase->nombre : 'Sin categoría',
            ];
        });

        // Group mapped detalles by their root_cuenta_base_name
        $detallesAgrupados = $mappedDetalles->groupBy('root_cuenta_base_name');

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Show', [
            'estadoFinanciero' => $estadoFinanciero,
            'detallesAgrupados' => $detallesAgrupados,
        ]);
    }

    public function edit(Empresa $empresa, $estadoFinancieroId)
    {
        $estadoFinanciero = $empresa->estadosFinancieros()->findOrFail($estadoFinancieroId);

        // The check if ($estadoFinanciero->empresa_id !== $empresa->id) is now redundant
        // because findOrFail($estadoFinancieroId) is scoped to $empresa->estadosFinancieros()
        // If the model is not found within the company's financial statements, a 404 will be thrown by findOrFail.

        $estadoFinanciero->load(['detalles.catalogoCuenta.cuentaBase']);

        // Fetch CatalogoCuentas for the current company and eager load their cuentaBase and its parent hierarchy
        $catalogoCuentas = $empresa->catalogoCuentas()->with(['cuentaBase' => function ($query) {
            $query->with('parentRecursive');
        }])->get();

        // Group CatalogoCuentas by their top-level CuentaBase (e.g., Activo, Pasivo)
        $cuentasBaseRaiz = CuentaBase::whereNull('parent_id')->with('childrenRecursive.catalogoCuentas')->get();

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Edit', [
            'empresa' => $empresa,
            'estadoFinanciero' => $estadoFinanciero,
            // 'cuentasBaseRaiz' => $cuentasBaseRaiz, // Removed as it's not used in the frontend
            'catalogoCuentas' => $catalogoCuentas->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre_cuenta, // Corrected to nombre_cuenta
                    'codigo' => $item->codigo_cuenta, // Corrected to codigo_cuenta
                    'cuenta_base_id' => $item->cuenta_base_id,
                    'cuenta_base_nombre' => $item->cuentaBase->nombre ?? null,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEstadoFinancieroRequest $request, Empresa $empresa, $estadoFinancieroId)
    {
        $estadoFinanciero = $empresa->estadosFinancieros()->findOrFail($estadoFinancieroId);

        // The check if ($estadoFinanciero->empresa_id !== $empresa->id) is now redundant
        // because findOrFail($estadoFinancieroId) is scoped to $empresa->estadosFinancieros()
        // If the model is not found within the company's financial statements, a 404 will be thrown by findOrFail.

        DB::transaction(function () use ($request, $estadoFinanciero) {
            $estadoFinanciero->update([
                'anio' => $request->anio,
                'tipo_estado' => $request->tipo_estado,
            ]);

            $currentDetalleIds = $estadoFinanciero->detalles->pluck('id')->toArray();
            $incomingDetalleIds = [];

            foreach ($request->detalles as $detalleData) {
                if (isset($detalleData['id'])) {
                    // Update existing detail
                    $detalle = $estadoFinanciero->detalles()->find($detalleData['id']);
                    if ($detalle) {
                        $detalle->update([
                            'catalogo_cuenta_id' => $detalleData['catalogo_cuenta_id'],
                            'valor' => $detalleData['valor'],
                        ]);
                        $incomingDetalleIds[] = $detalle->id;
                    }
                } else {
                    // Create new detail
                    $newDetalle = $estadoFinanciero->detalles()->create([
                        'catalogo_cuenta_id' => $detalleData['catalogo_cuenta_id'],
                        'valor' => $detalleData['valor'],
                    ]);
                    $incomingDetalleIds[] = $newDetalle->id;
                }
            }

            // Delete details that are no longer in the request
            $detallesToDelete = array_diff($currentDetalleIds, $incomingDetalleIds);
            if (!empty($detallesToDelete)) {
                DetalleEstado::whereIn('id', $detallesToDelete)->delete();
            }
        });

        return redirect()->route('empresas.estados-financieros.index', $empresa)
                         ->with('success', 'Estado Financiero actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa, $estadoFinancieroId)
    {
        $estadoFinanciero = $empresa->estadosFinancieros()->findOrFail($estadoFinancieroId);

        // The check if ($estadoFinanciero->empresa_id !== $empresa->id) is now redundant
        // because findOrFail($estadoFinancieroId) is scoped to $empresa->estadosFinancieros()
        // If the model is not found within the company's financial statements, a 404 will be thrown by findOrFail.

        $estadoFinanciero->delete();

        return redirect()->route('empresas.estados-financieros.index', $empresa)
                         ->with('success', 'Estado Financiero eliminado exitosamente.');
    }
}
