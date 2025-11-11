<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\EstadoFinanciero;
use App\Models\RatioCalculado;
use App\Models\CatalogoCuenta;
use Illuminate\Database\Console\DumpCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculoRatiosService
{
    /**
     * Calcula ratios financieros para TODAS las empresas y años disponibles
     */
    public function calcularYGuardarParaTodasLasEmpresas(): void
    {
        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            Log::warning("❌ No hay empresas registradas para calcular ratios.");
            return;
        }

        foreach ($empresas as $empresa) {
            Log::info("📊 Calculando ratios para la empresa {$empresa->id} - {$empresa->nombre}");
            echo "Calculando ratios para la empresa {$empresa->id} - {$empresa->nombre}\n";
            $this->calcularYGuardarPorEmpresa($empresa->id);
        }

        Log::info("✅ Cálculo de ratios completado para todas las empresas.");
    }

    /**
     * Calcula y guarda ratios financieros para todos los años de una empresa
     */
    public function calcularYGuardarPorEmpresa(int $empresaId): array
    {
        $anios = EstadoFinanciero::where('empresa_id', $empresaId)
            ->distinct()
            ->pluck('anio');

        if ($anios->isEmpty()) {
            Log::warning("⚠️ No existen estados financieros para la empresa {$empresaId}.");
            echo "No existen estados financieros para la empresa {$empresaId}.";
            return [];
        }

        $resultados = [];

        foreach ($anios as $anio) {
            $resultados[$anio] = $this->calcularYGuardar($empresaId, $anio);
            echo "Ratios para el año {$anio} calculados.\n";
        }

        return $resultados;
    }

    /**
     * Calcula y guarda ratios financieros para una empresa y año específico
     */
    public function calcularYGuardar(int $empresaId, int $anio): array
    {
        // Cargar estados financieros con detalles y catálogo
        $balance = EstadoFinanciero::where('empresa_id', $empresaId)
            ->where('anio', $anio)
            ->where('tipo_estado', 'balance_general')
            ->with('detalles.catalogoCuenta')
            ->first();



        $resultado = EstadoFinanciero::where('empresa_id', $empresaId)
            ->where('anio', $anio)
            ->whereIn('tipo_estado', ['estado_resultado', 'estado_resultados'])
            ->with('detalles.catalogoCuenta')
            ->first();

        if (!$balance || !$resultado) {
            Log::warning("❗ No se encontraron estados completos para empresa {$empresaId}, año {$anio}.");
            return [];
        }



        // DESPUÉS DE LA CORRECCIÓN DE NOMBRES DE CUENTA:
//$activoCorriente  = $this->obtenerValor($balance, 'ACTIVO CORRIENTE');
//$pasivoCorriente  = $this->obtenerValor($balance, 'PASIVO CORRIENTE');
//$inventario = $this->obtenerValor($balance, 'INVENTARIOS'); // <- CORREGIDO
//$activoTotal  = $this->obtenerValor($balance, 'ACTIVO'); // <- CORREGIDO
//$pasivoTotal = $this->obtenerValor($balance, 'PASIVO'); // <- CORREGIDO
//$patrimonio = $this->obtenerValor($balance, 'PATRIMONIO');
//$ventasNetas = $this->obtenerValor($resultado, 'VENTAS'); // Corregido el caso, aunque 'Ventas' también funcionaría por strtoupper
//$costoVentas  = $this->obtenerValor($resultado, 'COSTO DE VENTAS'); // <- CORREGIDO
//$utilidadNeta = $this->obtenerValor($resultado, 'Utilidad del Ejercicio');
// === Buscar valores usando código de cuenta ===
// (ajusta los códigos según tu catálogo_cuentas)
        $activoCorriente = $this->obtenerValor($balance, 'ACTIVO CORRIENTE');
        $pasivoCorriente = $this->obtenerValor($balance, 'PASIVO CORRIENTE');
        $inventario = $this->obtenerValor($balance, 'INVENTARIOS');
        $activoTotal = $this->obtenerValor($balance, 'ACTIVO');
        $pasivoTotal = $this->obtenerValor($balance, 'PASIVO');
        $patrimonio = $this->obtenerValor($balance, 'PATRIMONIO');
        $ventasNetas = $this->obtenerValor($resultado, 'VENTAS');
        $costoVentas = $this->obtenerValor($resultado, 'COSTO DE VENTAS');
        $utilidadNeta = $this->obtenerValor($resultado, 'Utilidad del Ejercicio');

        echo ("🔢 Valores obtenidos para empresa {$empresaId}, año {$anio}: Activo Corriente={$activoCorriente}, Pasivo Corriente={$pasivoCorriente}, Inventario={$inventario}, Activo Total={$activoTotal}, Pasivo Total={$pasivoTotal}, Patrimonio={$patrimonio}, Ventas Netas={$ventasNetas}, Costo de Ventas={$costoVentas}, Utilidad Neta={$utilidadNeta}");



        // Cálculo de ratios
        $ratios = [
            'Razón Circulante' => $this->div($activoCorriente, $pasivoCorriente),
            'Prueba Ácida' => $this->div(($activoCorriente - $inventario), $pasivoCorriente),
            'Razón de Capital de Trabajo' => $this->div(($activoCorriente - $pasivoCorriente), $activoTotal),
            'Rotación de Inventario' => $this->div($costoVentas, $inventario),
            'Días de Inventario' => $this->div(365, $this->div($costoVentas, $inventario)),
            'Rotación de Activos Totales' => $this->div($ventasNetas, $activoTotal),
            'Grado de Endeudamiento' => $this->div($pasivoTotal, $activoTotal),
            'Endeudamiento Patrimonial' => $this->div($pasivoTotal, $patrimonio),
            'Rentabilidad del Activo (ROA)' => $this->div($utilidadNeta, $activoTotal),
            'Rentabilidad del Patrimonio (ROE)' => $this->div($utilidadNeta, $patrimonio),
        ];

        dump($ratios);
        try{
            echo "Guardando ratios para empresa {$empresaId}, año {$anio}...\n";
            DB::transaction(function () use ($empresaId, $anio, $ratios) {
                RatioCalculado::where('empresa_id', $empresaId)
                    ->where('anio', $anio)
                    ->delete();
    
                foreach ($ratios as $nombre => $valor) {
                    RatioCalculado::create([
                        'empresa_id' => $empresaId,
                        'anio' => $anio,
                        'nombre_ratio' => $nombre,
                        'valor_ratio' => round($valor, 4),
                    ]);
                }
            });
        }   catch (\Exception $e) {
            Log::error("❌ Error al guardar ratios para empresa {$empresaId}, año {$anio}: " . $e->getMessage());
            echo "Error al guardar ratios para empresa {$empresaId}, año {$anio}: " . $e->getMessage();
        }
        // Guardar ratios calculados

        Log::info("✅ Ratios calculados y guardados para empresa {$empresaId}, año {$anio}");
        return $ratios;
    }

    /** Evita divisiones por cero */
    private function div(float $a, float $b): float
    {
        return $b == 0 ? 0 : $a / $b;
    }

    /** Busca un valor en los detalles del estado según nombre del catálogo */
    private function obtenerValor(EstadoFinanciero $estado, string $nombreCuenta): float
    {
        // Normalizamos el nombre (IMPORTANTE: usamos $buscar para la consulta)
        $buscar = $this->normalizarTexto($nombreCuenta);

        // El Log::info es útil, lo mantenemos.
        Log::info("🔍 Buscando cuenta: {$buscar}");

        // 1. Encontrar la Cuenta Mayor
        // NOTA: Usamos $buscar para la consulta para asegurar coincidencia con el nombre normalizado.
        $cuentaMayor = CatalogoCuenta::where('nombre_cuenta', $buscar)->first();

        // Verificación de existencia
        if (!$cuentaMayor) {
            Log::warning("⛔ Cuenta Mayor no encontrada: {$buscar}");
            // Se sugiere usar la lógica de búsqueda flexible si falla la coincidencia exacta:
            // $cuentaMayor = CatalogoCuenta::where('nombre_cuenta', 'like', "%{$buscar}%")->first();
            return 0.0;
        }

        $codigoMayor = $cuentaMayor->codigo_cuenta;
        Log::info("✅ Cuenta Mayor encontrada: {$cuentaMayor->nombre_cuenta} ({$codigoMayor})");

        // 2. Filtrar la Colección para obtener los detalles coincidentes
        $detallesFiltrados = $estado->detalles->filter(function ($detalle) use ($codigoMayor) {

            // Si la relación no existe, excluimos el detalle
            if (!$detalle->catalogoCuenta) {
                return false;
            }

            $codigoDetalle = $detalle->catalogoCuenta->codigo_cuenta;

            // El 'return' DEBE ser booleano para que filter funcione
            return str_starts_with($codigoDetalle, $codigoMayor);
        });

        // 3. Sumar los valores de la Colección Filtrada
        // Usamos el método sum() de la Colección de Laravel, que es la forma idiomática.
        $total = $detallesFiltrados->sum('valor');

        // Logueamos el resultado antes de retornar
        Log::info("💰 Total calculado para {$codigoMayor}: {$total}");

        // 4. Retornar el valor sumado
        return $total;
    }

    /**
     * Quita tildes, pasa a mayúsculas y limpia espacios dobles.
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = strtoupper(trim($texto));
        $texto = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'N'],
            $texto
        );
        return preg_replace('/\s+/', ' ', $texto);
    }



}
