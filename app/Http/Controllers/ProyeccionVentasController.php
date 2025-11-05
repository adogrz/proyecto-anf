<?php

namespace App\Http\Controllers;

use App\Models\DatoVentaHistorico;
use App\Models\ProyeccionVenta;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProyeccionVentasController extends Controller
{
    private function getUserPermissions($user): array
    {
        return [
            'canCreate' => $user?->can('create', ProyeccionVenta::class) ?? false,
            'canEdit' => $user?->can('proyecciones.edit') ?? false,
            'canDelete' => $user?->can('proyecciones.delete') ?? false,
        ];
    }

    use AuthorizesRequests;
    public function dashboard(Request $request, $empresa): Response
    {
        $this->authorize('viewAny', ProyeccionVenta::class);

        $user = $request->user();

        $datosVentaHistorico = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return Inertia::render('ProyeccionVentas/dashboard-proyeccion-ventas', [
            'datosVentaHistorico' => $datosVentaHistorico,
            'permissions' => $this->getUserPermissions($user),
        ]);
    }

    public function generar(Request $request, $empresa): Response
    {
        return Inertia::render('ProyeccionVentas/resultados-proyeccion-ventas');
    }
}
