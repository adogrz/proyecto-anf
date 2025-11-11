<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PlantillaCatalogo;
use App\Services\CatalogoService;
use App\Services\EstadoFinancieroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportacionController extends Controller
{
    protected $catalogoService;
    protected $estadoFinancieroService;

    public function __construct(CatalogoService $catalogoService, EstadoFinancieroService $estadoFinancieroService)
    {
        $this->catalogoService = $catalogoService;
        $this->estadoFinancieroService = $estadoFinancieroService;
    }

    public function wizard()
    {
        return Inertia::render('Importacion/Wizard', [
            'plantillas' => \App\Models\PlantillaCatalogo::with('cuentasBase')->get(),
            'empresas' => \App\Models\Empresa::with('sector')->get(), // Incluir relación sector
            'sectores' => \App\Models\Sector::orderBy('nombre')->get(),
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'plantilla_id' => 'required|exists:plantillas_catalogo,id'
        ]);

        try {
            $result = $this->catalogoService->procesarAutomap(
                $request->file('file')->getRealPath(),
                $request->input('plantilla_id')
            );

            return response()->json([
                'success' => true,
                'preview' => array_slice($result['datos'], 0, 5),
                'total_rows' => count($result['datos']),
                'stats' => $result['stats'],
                'errores' => $result['errores']
            ]);

        } catch (\Exception $e) {
            Log::error('Error en preview: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function crearEmpresa(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('empresas', 'nombre')->whereNull('deleted_at')
                ],
                'sector_id' => 'required|exists:sectores,id',
                'nombre_plantilla' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('plantillas_catalogo', 'nombre')->whereNull('deleted_at')
                ],
            ], [
                'nombre.unique' => 'Ya existe una empresa con el nombre :input',
                'nombre_plantilla.unique' => 'Ya existe una plantilla con el nombre :input',
            ]);

            return DB::transaction(function () use ($validated) {
                $plantilla = PlantillaCatalogo::create([
                    'nombre' => $validated['nombre_plantilla'],
                    'descripcion' => 'Plantilla creada automáticamente para ' . $validated['nombre'],
                ]);

                $empresa = Empresa::create([
                    'nombre' => $validated['nombre'],
                    'sector_id' => $validated['sector_id'],
                    'plantilla_catalogo_id' => $plantilla->id,
                ]);

                return response()->json([
                    'success' => true,
                    'empresa' => $empresa->load('sector', 'plantillaCatalogo'),
                    'message' => 'Empresa y plantilla creadas correctamente'
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Error de validación'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando empresa y plantilla: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function automap(Request $request)
    {
        try {
            // 1. Validación más estricta
            $validated = $request->validate([
                'file' => 'required_without:archivo|file|mimes:xlsx,xls,csv',
                'archivo' => 'required_without:file|file|mimes:xlsx,xls,csv',
                'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
            ]);

            // 2. Obtener y validar archivo
            $file = $request->file('file') ?? $request->file('archivo');
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo inválido o no enviado'
                ], 422);
            }

            // 3. Obtener plantilla_id
            $plantillaId = (int) $request->input('plantilla_catalogo_id');

            // 4. Verificar que la plantilla existe
            $plantilla = PlantillaCatalogo::findOrFail($plantillaId);

            // 5. Procesar archivo con mejor manejo de errores
            try {
                $result = $this->catalogoService->procesarAutomap(
                    $file->getRealPath(), 
                    $plantillaId
                );

                // 6. Validar estructura del resultado
                if (!isset($result['datos']) || !isset($result['errores'])) {
                    throw new \Exception('Formato de respuesta inválido del servicio');
                }

                return response()->json([
                    'success' => true,
                    'datos' => $result['datos'],
                    'errores' => $result['errores'],
                    'warnings' => $result['warnings'] ?? [],
                    'stats' => $result['stats'] ?? [],
                ]);

            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                Log::error('Error validación Excel: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error en el formato del archivo',
                    'errors' => $e->failures()
                ], 422);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en automap: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    public function previsualizarEstadoFinanciero(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
            'empresa_id' => 'required|exists:empresas,id',
            'anio' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'tipo_estado' => 'required|in:balance_general,estado_resultados',
        ]);

        $file = $request->file('archivo');
        $empresaId = $request->input('empresa_id');
        $anio = $request->input('anio');
        $tipoEstado = $request->input('tipo_estado');

        $resultado = $this->estadoFinancieroService->previsualizar($file, $empresaId, $anio, $tipoEstado);

        return response()->json($resultado);
    }

    public function guardarMapeo(Request $request)
    {
        Log::debug('ImportacionController: Inicia guardarMapeo'); // Add this line

        $validatedData = $request->validate([ // Store validated data
            'empresa_id' => 'required|exists:empresas,id',
            'cuentas' => 'required|array',
            'cuentas.*.codigo_cuenta' => 'required|string|max:255',
            'cuentas.*.nombre_cuenta' => 'required|string|max:255',
            'cuentas.*.cuenta_base_id' => 'nullable|exists:cuentas_base,id',
        ]);

        try { // Add try block
            $this->catalogoService->guardarMapeo($validatedData); // Pass the validated data array

            return response()->json(['message' => 'Mapeo guardado con éxito.']);
        } catch (\Exception $e) { // Add catch block
            Log::error('ImportacionController: Error al guardar mapeo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error al guardar el mapeo: ' . $e->getMessage(),
                'server_error' => $e->getMessage(),
            ], 500);
        }
    }

    public function previsualizarCatalogoBase(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('archivo');
            $resultado = $this->catalogoService->procesarCatalogoBasePreview($file->getRealPath());

            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error("Error en previsualizarCatalogoBase: " . $e->getMessage());
            return response()->json([
                'errores' => ['Se produjo un error inesperado al procesar el archivo.'],
                'warnings' => [],
                'datos' => [],
            ], 500);
        }
    }

    public function importarCatalogoBase(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
            'empresa_id' => 'required|exists:empresas,id', // <--- Add this validation rule
        ]);

        try {
            $file = $request->file('archivo');
            $plantillaId = $request->input('plantilla_catalogo_id');
            $empresaId = $request->input('empresa_id'); // <--- Get empresa_id from request

            $result = $this->catalogoService->importarCuentasBase($file, $plantillaId, $empresaId); // <--- Pass empresaId

            return response()->json([
                'message' => 'Catálogo base importado con éxito.',
                'warnings' => $result['warnings']
            ]);
        } catch (\Exception $e) {
            Log::error("Error en importarCatalogoBase: " . $e->getMessage());
            return response()->json([
                'message' => 'Se produjo un error al importar el catálogo base.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function guardarEstadoFinanciero(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'anio' => 'required|integer|min:1900|max:' . (date('Y') + 10),
            'tipo_estado' => 'required|in:balance_general,estado_resultados',
            'detalles' => 'required|array',
            'detalles.*.codigo_cuenta' => 'required|string|max:255',
            'detalles.*.cuenta_base_id' => 'required|exists:cuentas_base,id',
            'detalles.*.saldo' => 'required|numeric',
            'detalles.*.fecha' => 'nullable',
            'detalles.*.periodo' => 'nullable',
        ]);

        $empresaId = $request->input('empresa_id');
        $anio = $request->input('anio');
        $tipoEstado = $request->input('tipo_estado');
        $detalles = $request->input('detalles');

        try {
            $this->estadoFinancieroService->guardar($empresaId, $anio, $tipoEstado, $detalles);
            
            // Return a JSON response instead of a redirect
            return response()->json([
                'success' => true,
                'message' => 'Estado financiero guardado con éxito.',
                'empresa_id' => $empresaId, // Pass empresa_id for potential redirection
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar estado financiero: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Return a JSON error response
            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar el estado financiero: ' . $e->getMessage(),
                'server_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descarga plantilla de ejemplo según el tipo (xlsx por defecto, csv opcional)
     */
    public function descargarPlantilla(Request $request, $tipo = 'catalogo')
    {
        $format = strtolower($request->query('format', 'xlsx')); // 'xlsx' or 'csv'

        // Preparar cabeceras y ejemplos
        switch ($tipo) {
            case 'balance':
                $headers = ['codigo_cuenta', 'nombre_cuenta', 'saldo'];
                $rows = [
                    ['1.1.1', 'Caja y Bancos', '10000.00'],
                    ['1.1.2', 'Cuentas por Cobrar', '25000.00'],
                ];
                break;
            case 'resultados':
                $headers = ['codigo_cuenta', 'nombre_cuenta', 'saldo', 'periodo'];
                $rows = [
                    ['4.1.1', 'Ventas', '100000.00', '2025-12'],
                    ['5.1.1', 'Costo de Ventas', '-60000.00', '2025-12'],
                ];
                break;
            case 'catalogo':
            default:
                $headers = ['codigo_cuenta', 'nombre_cuenta'];
                $rows = [
                    ['1', 'ACTIVO'],
                    ['1.1', 'ACTIVO CORRIENTE'],
                    ['1.1.1', 'EFECTIVO Y EQUIVALENTES'],
                    ['2', 'PASIVO'],
                    ['2.1', 'PASIVO CORRIENTE'],
                    ['2.1.1', 'PROVEEDORES'],
                    ['3', 'PATRIMONIO'],
                    ['3.1', 'CAPITAL SOCIAL'],
                    ['4', 'INGRESOS'],
                    ['4.1', 'VENTAS'],
                    ['5', 'COSTOS'],
                    ['5.1', 'COSTO DE VENTAS'],
                    ['6', 'GASTOS'],
                    ['6.1', 'GASTOS DE ADMINISTRACION'],
                ];
                break;
        }

        // Construir hoja con PhpSpreadsheet (misma estructura para csv/xlsx para evitar columnas combinadas)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Escribir cabeceras (fila 1)
        $sheet->fromArray($headers, NULL, 'A1');

        // Escribir filas de ejemplo a partir de la fila 2
        $rowIndex = 2;
        foreach ($rows as $r) {
            $col = 1;
            foreach ($r as $cell) {
                // Forzar texto para evitar que Excel interprete mal formatos
                $sheet->setCellValueExplicitByColumnAndRow($col++, $rowIndex, (string)$cell, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $rowIndex++;
        }

        if ($format === 'csv') {
            $filename = "plantilla_{$tipo}.csv";

            $writer = new Csv($spreadsheet);
            // Use comma as delimiter for better compatibility
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            // Include BOM so Excel detects UTF-8 properly
            if (method_exists($writer, 'setUseBOM')) {
                $writer->setUseBOM(true);
            }

            $callback = function() use ($writer) {
                // PhpSpreadsheet Csv writer writes to php://output
                $writer->save('php://output');
            };

            return response()->streamDownload($callback, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\""
            ]);
        }

        // Default: generar XLSX
        $writerXlsx = new Xlsx($spreadsheet);
        $filenameXlsx = "plantilla_{$tipo}.xlsx";

        $callbackXlsx = function() use ($writerXlsx) {
            $writerXlsx->save('php://output');
        };

        return response()->streamDownload($callbackXlsx, $filenameXlsx, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filenameXlsx}\""
        ]);
    }



    /**
     * Ruta para la documentación de formatos
     */
    public function documentacion()
    {
        return Inertia::render('Importacion/Documentacion', [
            'estructuras' => [
                'catalogo' => [
                    'descripcion' => 'Catálogo de cuentas contables',
                    'columnas' => [
                        'codigo_cuenta' => 'Código jerárquico (ej: 1.1.1)',
                        'nombre_cuenta' => 'Nombre descriptivo',
                        'descripcion' => 'Descripción detallada (opcional)',
                        'naturaleza' => 'D=Débito, H=Haber (opcional)'
                    ],
                    'ejemplo' => '1.1.1,Caja,Efectivo disponible,D'
                ],
                'balance' => [
                    'descripcion' => 'Balance General',
                    'columnas' => [
                        'codigo_cuenta' => 'Código del catálogo',
                        'saldo' => 'Monto (usar punto decimal)',
                        'fecha' => 'Fecha del balance (YYYY-MM-DD)'
                    ],
                    'ejemplo' => '1.1.1,10000.00,2025-12-31'
                ],
                'resultados' => [
                    'descripcion' => 'Estado de Resultados',
                    'columnas' => [
                        'codigo_cuenta' => 'Código del catálogo',
                        'saldo' => 'Monto (negativo para gastos)',
                        'periodo' => 'Periodo contable (YYYY-MM)'
                    ],
                    'ejemplo' => '4.1.1,100000.00,2025-12'
                ]
            ]
        ]);
    }
}
