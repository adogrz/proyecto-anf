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
    public function index(Empresa $empresa)
    {
        $empresa->load('estadosFinancieros');

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Index', [
            'empresa' => $empresa,
            'estadosFinancieros' => $empresa->estadosFinancieros,
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
    public function show(Empresa $empresa, $estadoFinancieroId) // Changed parameter name to $estadoFinancieroId
    {
        $estadoFinanciero = $empresa->estadosFinancieros()->findOrFail($estadoFinancieroId); // Manually find and scope

        $estadoFinanciero->load(['empresa', 'detalles.catalogoCuenta.cuentaBase']);

        // Agrupar detalles por tipo de cuenta base (Activo, Pasivo, etc.)
        $detallesAgrupados = $estadoFinanciero->detalles->groupBy(function ($detalle) {
            // Navegar a través de las relaciones para encontrar el ancestro raíz
            $cuentaBase = $detalle->catalogoCuenta->cuentaBase;
            while ($cuentaBase && $cuentaBase->parent_id) {
                $cuentaBase = $cuentaBase->parent;
            }
            return $cuentaBase ? $cuentaBase->nombre : 'Sin categoría';
        });

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Show', [
            'estadoFinanciero' => $estadoFinanciero,
            'detallesAgrupados' => $detallesAgrupados,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa, EstadoFinanciero $estadoFinanciero)
    {
        // Ensure the estadoFinanciero belongs to the given empresa
        if ($estadoFinanciero->empresa_id !== $empresa->id) {
            abort(404);
        }

        $estadoFinanciero->load(['detalles.catalogoCuenta.cuentaBase']);

        // Fetch all CatalogoCuentas and eager load their cuentaBase and its parent hierarchy
        $catalogoCuentas = CatalogoCuenta::with(['cuentaBase' => function ($query) {
            $query->with('parentRecursive');
        }])->get();

        // Group CatalogoCuentas by their top-level CuentaBase (e.g., Activo, Pasivo)
        $cuentasBaseRaiz = CuentaBase::whereNull('parent_id')->with('childrenRecursive.catalogoCuentas')->get();

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Edit', [
            'empresa' => $empresa,
            'estadoFinanciero' => $estadoFinanciero,
            'cuentasBaseRaiz' => $cuentasBaseRaiz,
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
     * Update the specified resource in storage.
     */
    public function update(UpdateEstadoFinancieroRequest $request, Empresa $empresa, EstadoFinanciero $estadoFinanciero)
    {
        // Ensure the estadoFinanciero belongs to the given empresa
        if ($estadoFinanciero->empresa_id !== $empresa->id) {
            abort(404);
        }

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
    public function destroy(Empresa $empresa, EstadoFinanciero $estadoFinanciero)
    {
        // Ensure the estadoFinanciero belongs to the given empresa
        if ($estadoFinanciero->empresa_id !== $empresa->id) {
            abort(404);
        }

        $estadoFinanciero->delete();

        return redirect()->route('empresas.estados-financieros.index', $empresa)
                         ->with('success', 'Estado Financiero eliminado exitosamente.');
    }
}
