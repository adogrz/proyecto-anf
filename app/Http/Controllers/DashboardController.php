<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\EstadoFinanciero;
use App\Models\RatioCalculado;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Métricas generales
        $stats = [
            'empresas_total' => Empresa::count(),
            'sectores_total' => Sector::count(),
            'estados_financieros_total' => EstadoFinanciero::count(),
            'ratios_calculados_total' => RatioCalculado::count(),
            'usuarios_total' => User::count(),
        ];

        // Empresas por sector (para gráfico)
        $empresasPorSector = Sector::withCount('empresas')
            ->get()
            ->filter(function ($sector) {
                return $sector->empresas_count > 0;
            })
            ->map(function ($sector) {
                return [
                    'sector' => $sector->nombre,
                    'cantidad' => $sector->empresas_count,
                ];
            })
            ->values();

        // Actividad reciente - últimos estados financieros
        $actividadReciente = EstadoFinanciero::with(['empresa'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($estado) {
                return [
                    'empresa' => $estado->empresa->nombre,
                    'accion' => 'Subió estado financiero - ' . $estado->tipo,
                    'fecha' => $estado->created_at->format('Y-m-d'),
                    'fecha_relativa' => $estado->created_at->diffForHumans(),
                ];
            });

        // Top empresas con más estados financieros
        $topEmpresas = Empresa::withCount('estadosFinancieros')
            ->get()
            ->filter(function ($empresa) {
                return $empresa->estados_financieros_count > 0;
            })
            ->sortByDesc('estados_financieros_count')
            ->take(5)
            ->map(function ($empresa) {
                return [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'estados_count' => $empresa->estados_financieros_count,
                ];
            })
            ->values();

        // Estados financieros por mes (últimos 6 meses)
        $estadosPorMes = EstadoFinanciero::select(
            DB::raw("strftime('%Y-%m', created_at) as mes"),
            DB::raw('COUNT(*) as cantidad')
        )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                return [
                    'mes' => $item->mes,
                    'cantidad' => $item->cantidad,
                ];
            });

        return Inertia::render('dashboard', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'ultimo_acceso' => $user->updated_at->format('d/m/Y'),
                'roles' => $user->roles->pluck('name')->toArray(),
            ],
            'stats' => $stats,
            'empresas_por_sector' => $empresasPorSector,
            'actividad_reciente' => $actividadReciente,
            'top_empresas' => $topEmpresas,
            'estados_por_mes' => $estadosPorMes,
        ]);
    }
}
