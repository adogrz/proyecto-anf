<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PlantillaCatalogo;
use App\Models\CuentaBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class ImportacionCuentasBaseController extends Controller
{
    private function parseFile($filePath, $extension)
    {
        $data = [];
        $errors = [];
        $rowNumber = 1;

        if ($extension === 'csv' || $extension === 'txt') {
            $handle = fopen($filePath, "r");
            $headerSkipped = false;
            while (($csvLine = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (!$headerSkipped) {
                    $headerSkipped = true;
                    continue;
                }
                if (count($csvLine) >= 4) {
                    $data[] = [
                        'codigo' => $csvLine[0],
                        'nombre' => $csvLine[1],
                        'tipo_cuenta' => $csvLine[2],
                        'naturaleza' => $csvLine[3],
                        'parent_codigo' => $csvLine[4] ?? null,
                        'row' => $rowNumber++,
                    ];
                } else {
                    $errors[] = "Fila {$rowNumber}: Formato de fila incorrecto. Se esperaban al menos 4 columnas.";
                    $rowNumber++;
                }
            }
            fclose($handle);
        } elseif ($extension === 'xlsx' || $extension === 'xls') {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $sheet->getHighestColumn() . $row,
                    NULL, TRUE, FALSE);
                $csvLine = $rowData[0];

                if (count($csvLine) >= 4) {
                    $data[] = [
                        'codigo' => $csvLine[0],
                        'nombre' => $csvLine[1],
                        'tipo_cuenta' => $csvLine[2],
                        'naturaleza' => $csvLine[3],
                        'parent_codigo' => $csvLine[4] ?? null,
                        'row' => $row,
                    ];
                } else {
                    $errors[] = "Fila {$row}: Formato de fila incorrecto. Se esperaban al menos 4 columnas.";
                }
            }
        } else {
            $errors[] = "Tipo de archivo no soportado.";
        }

        return ['data' => $data, 'errors' => $errors];
    }

    public function preview(Request $request)
    {
        $request->validate([
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();

        try {
            $parsed = $this->parseFile($filePath, $extension);
            $data = $parsed['data'];
            $parsingErrors = $parsed['errors'];

            // Prepare data for preview, showing only relevant columns
            $previewData = array_map(function($row) {
                return [
                    'codigo' => $row['codigo'],
                    'nombre' => $row['nombre'],
                    'tipo_cuenta' => $row['tipo_cuenta'],
                    'naturaleza' => $row['naturaleza'],
                    'parent_codigo' => $row['parent_codigo'],
                ];
            }, array_slice($data, 0, 10)); // Show first 10 rows as preview

            return response()->json([
                'headers' => ['Código', 'Nombre', 'Tipo de Cuenta', 'Naturaleza', 'Código Padre'],
                'preview' => $previewData,
                'total_rows' => count($data),
                'parsing_errors' => $parsingErrors,
            ]);
        } catch (ReaderException $e) {
            Log::error('Error al leer el archivo de hoja de cálculo: ' . $e->getMessage());
            return response()->json(['error' => 'Error al leer el archivo Excel. Asegúrate de que sea un archivo válido.'], 422);
        } catch (Exception $e) {
            Log::error('Error al procesar el archivo: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar el archivo. ' . $e->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $plantillaId = $request->plantilla_catalogo_id;
        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();

        $cuentasParaInsertar = [];
        $errors = [];

        try {
            $parsed = $this->parseFile($filePath, $extension);
            $cuentasParaInsertar = $parsed['data'];
            $errors = array_merge($errors, $parsed['errors']);
        } catch (ReaderException $e) {
            Log::error('Error al leer el archivo de hoja de cálculo: ' . $e->getMessage());
            return redirect()->back()->withErrors(['file' => 'Error al leer el archivo Excel. Asegúrate de que sea un archivo válido.'])->withInput();
        } catch (Exception $e) {
            Log::error('Error al procesar el archivo: ' . $e->getMessage());
            return redirect()->back()->withErrors(['file' => 'Error al procesar el archivo. ' . $e->getMessage()])->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($cuentasParaInsertar as $cuentaData) {
                $parentId = null;
                if (!empty($cuentaData['parent_codigo'])) {
                    $parent = CuentaBase::where('codigo', $cuentaData['parent_codigo'])
                                        ->where('plantilla_catalogo_id', $plantillaId)
                                        ->first();
                    if (!$parent) {
                        $errors[] = "Fila {$cuentaData['row']}: La cuenta padre con código '{$cuentaData['parent_codigo']}' no existe en esta plantilla.";
                        continue;
                    }
                    $parentId = $parent->id;
                }

                $validator = Validator::make($cuentaData, [
                    'codigo' => 'required|string|max:255',
                    'nombre' => 'required|string|max:255',
                    'tipo_cuenta' => 'required|string|in:AGRUPACION,DETALLE',
                    'naturaleza' => 'required|string|in:DEUDORA,ACREEDORA',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $message) {
                        $errors[] = "Fila {$cuentaData['row']}: " . $message;
                    }
                    continue;
                }

                CuentaBase::create([
                    'plantilla_catalogo_id' => $plantillaId,
                    'codigo' => $cuentaData['codigo'],
                    'nombre' => $cuentaData['nombre'],
                    'tipo_cuenta' => $cuentaData['tipo_cuenta'],
                    'naturaleza' => $cuentaData['naturaleza'],
                    'parent_id' => $parentId,
                ]);
            }

            if (!empty($errors)) {
                throw new Exception("Se encontraron errores durante la importación.");
            }

            DB::commit();
            return redirect()->route('plantillas-catalogo.cuentas-base.index', ['plantilla_catalogo' => $plantillaId])
                             ->with('success', 'Cuentas base importadas con éxito.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en la importación de cuentas base: ' . $e->getMessage());
            $errorMessage = !empty($errors) ? implode("\n", $errors) : 'Error desconocido durante la importación.';
            return redirect()->back()->withErrors(['import' => $errorMessage])->withInput();
        }
    }
}


