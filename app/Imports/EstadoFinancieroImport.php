<?php

namespace App\Imports;

use App\Models\CatalogoCuenta;
use App\Models\DetalleEstado;
use App\Models\EstadoFinanciero;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;

class EstadoFinancieroImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnFailure
{
    private EstadoFinanciero $estadoFinanciero;
    private array $catalogoCuentasMap;
    private array $failures = [];

    public function __construct(int $empresaId, int $anio, string $tipoEstado)
    {
        $this->estadoFinanciero = EstadoFinanciero::firstOrCreate(
            [
                'empresa_id' => $empresaId,
                'anio' => $anio,
                'tipo_estado' => $tipoEstado,
            ],
            // Clear existing details only when creating a new record for the first time in this import
            function (EstadoFinanciero $ef) {
                $ef->detalles()->delete();
            }
        );

        $this->catalogoCuentasMap = CatalogoCuenta::where('empresa_id', $empresaId)
            ->pluck('id', 'codigo_cuenta')
            ->toArray();
    }

    public function model(array $row)
    {
        $codigoCuenta = $row['codigo_cuenta'];

        return new DetalleEstado([
            'estado_financiero_id' => $this->estadoFinanciero->id,
            'catalogo_cuenta_id' => $this->catalogoCuentasMap[$codigoCuenta],
            'valor' => $row['valor'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.codigo_cuenta' => ['required', function ($attribute, $value, $fail) {
                if (!isset($this->catalogoCuentasMap[$value])) {
                    $fail("El código de cuenta '{$value}' no existe o no está mapeado para esta empresa.");
                }
            }],
            '*.valor' => ['required', 'numeric'],
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

}