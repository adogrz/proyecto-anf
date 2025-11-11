<?php

namespace App\Http\Controllers;

use App\Models\CatalogoCuenta;
use App\Models\CuentaBase;
use App\Models\Empresa;
use App\Models\PlantillaCatalogo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CuentasBaseController extends Controller
{
    public function index(Empresa $empresa)
    {
        $plantillaCatalogo = $empresa->plantillaCatalogo;
        $cuentasBase = $plantillaCatalogo ? $plantillaCatalogo->cuentasBase()->with('parent')->get() : collect();

        $breadcrumbs = [
            ['title' => 'Empresas', 'href' => route('empresas.index')],
            ['title' => $empresa->nombre, 'href' => route('empresas.show', $empresa->id)],
            ['title' => 'Cuentas Base', 'href' => route('empresas.cuentas-base.index', ['empresa' => $empresa->id])],
        ];

        return Inertia::render('Administracion/CuentasBase/Index', [
            'cuentasBase' => $cuentasBase,
            'plantilla' => $plantillaCatalogo,
            'selectedPlantilla' => $plantillaCatalogo,
            'breadcrumbs' => $breadcrumbs,
            'empresa' => $empresa,
        ]);
    }

    public function create(Request $request, Empresa $empresa)
    {
        $plantillaCatalogo = $empresa->plantillaCatalogo;
        if (!$plantillaCatalogo) {
            return redirect()->route('empresas.show', $empresa->id)->with('error', 'La empresa no tiene una plantilla de catálogo asignada.');
        }
        $breadcrumbs = [
            ['title' => 'Empresas', 'href' => route('empresas.index')],
            ['title' => $empresa->nombre, 'href' => route('empresas.show', $empresa->id)],
            ['title' => 'Cuentas Base', 'href' => route('empresas.cuentas-base.index', ['empresa' => $empresa->id])],
            ['title' => 'Crear Cuenta Base', 'href' => route('empresas.cuentas-base.create', ['empresa' => $empresa->id])],
        ];

        $cuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogo->id)->get();
        return Inertia::render('Administracion/CuentasBase/Create', [
            'plantilla' => $plantillaCatalogo,
            'cuentasBase' => $cuentasBase,
            'breadcrumbs' => $breadcrumbs,
            'empresa' => $empresa,
        ]);
    }

    public function store(Request $request, Empresa $empresa)
    {
        $plantillaCatalogo = $empresa->plantillaCatalogo;
        if (!$plantillaCatalogo) {
            return back()->with('error', 'La empresa no tiene una plantilla de catálogo asignada.');
        }

        $request->validate([
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'tipo_cuenta' => ['required', 'string', Rule::in(['AGRUPACION', 'DETALLE'])],
            'naturaleza' => ['required', 'string', Rule::in(['DEUDORA', 'ACREEDORA'])],
            'parent_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $data = $request->all();
        $data['plantilla_catalogo_id'] = $plantillaCatalogo->id;

        CuentaBase::create($data);

        return redirect()->route('empresas.cuentas-base.index', ['empresa' => $empresa->id])
            ->with('success', 'Cuenta base creada con éxito.');
    }

    public function show(Empresa $empresa, CuentaBase $cuentas_base)
    {
        $cuentas_base->load('plantillaCatalogo', 'parent', 'children');

        $breadcrumbs = [
            ['title' => 'Empresas', 'href' => route('empresas.index')],
            ['title' => $empresa->nombre, 'href' => route('empresas.show', $empresa->id)],
            ['title' => 'Cuentas Base', 'href' => route('empresas.cuentas-base.index', ['empresa' => $empresa->id])],
            ['title' => $cuentas_base->nombre, 'href' => route('empresas.cuentas-base.show', ['empresa' => $empresa->id, 'cuentas_base' => $cuentas_base->id])],
        ];

        return Inertia::render('Administracion/CuentasBase/Show', [
            'cuentaBase' => $cuentas_base,
            'plantilla' => $empresa->plantillaCatalogo,
            'breadcrumbs' => $breadcrumbs,
            'empresa' => $empresa,
        ]);
    }

    public function edit(Empresa $empresa, CuentaBase $cuentas_base)
    {
        $cuentas_base->load('plantillaCatalogo', 'parent', 'children');
        $plantillaCatalogo = $empresa->plantillaCatalogo;

        $breadcrumbs = [
            ['title' => 'Empresas', 'href' => route('empresas.index')],
            ['title' => $empresa->nombre, 'href' => route('empresas.show', $empresa->id)],
            ['title' => 'Cuentas Base', 'href' => route('empresas.cuentas-base.index', ['empresa' => $empresa->id])],
            ['title' => $cuentas_base->nombre, 'href' => route('empresas.cuentas-base.show', ['empresa' => $empresa->id, 'cuentas_base' => $cuentas_base->id])],
            ['title' => 'Editar', 'href' => route('empresas.cuentas-base.edit', ['empresa' => $empresa->id, 'cuentas_base' => $cuentas_base->id])],
        ];

        $allCuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogo->id)->get();

        return Inertia::render('Administracion/CuentasBase/Edit', [
            'cuentaBase' => $cuentas_base,
            'plantilla' => $plantillaCatalogo,
            'allCuentasBase' => $allCuentasBase,
            'breadcrumbs' => $breadcrumbs,
            'empresa' => $empresa,
        ]);
    }

    public function update(Request $request, Empresa $empresa, CuentaBase $cuentas_base)
    {
        $request->validate([
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'tipo_cuenta' => ['required', 'string', Rule::in(['AGRUPACION', 'DETALLE'])],
            'naturaleza' => ['required', 'string', Rule::in(['DEUDORA', 'ACREEDORA'])],
            'parent_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $data = $request->all();
        $data['plantilla_catalogo_id'] = $empresa->plantilla_catalogo_id;
        $cuentas_base->update($data);

        return redirect()->route('empresas.cuentas-base.index', ['empresa' => $empresa->id])
            ->with('success', 'Cuenta base actualizada con éxito.');
    }

    public function destroy(Empresa $empresa, CuentaBase $cuentas_base)
    {
        $cuentas_base->delete();

        return redirect()->route('empresas.cuentas-base.index', ['empresa' => $empresa->id])
            ->with('success', 'Cuenta base eliminada con éxito.');
    }

    public function export(Empresa $empresa)
    {
        $plantillaCatalogo = $empresa->plantillaCatalogo;
        if (!$plantillaCatalogo) {
            return redirect()->route('empresas.show', $empresa->id)->with('error', 'La empresa no tiene una plantilla de catálogo asignada.');
        }
        $cuentas = CuentaBase::with('parent')->where('plantilla_catalogo_id', $plantillaCatalogo->id)->get();

        $fileName = 'cuentas_base_' . Str::slug($plantillaCatalogo->nombre) . '.xlsx';
        $fileName = rtrim($fileName, '_'); // Remove any trailing underscores

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $columns = ['codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'parent_codigo'];
        $sheet->fromArray($columns, NULL, 'A1');

        // Populate data
        $rowIndex = 2;
        foreach ($cuentas as $cuenta) {
            $rowData = [
                $cuenta->codigo,
                $cuenta->nombre,
                $cuenta->tipo_cuenta,
                $cuenta->naturaleza,
                $cuenta->parent ? $cuenta->parent->codigo : '',
            ];
            $sheet->fromArray($rowData, NULL, 'A' . $rowIndex++);
        }

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
