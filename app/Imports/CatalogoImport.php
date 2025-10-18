<?php

namespace App\Imports;

use App\Models\CatalogoCuenta;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CatalogoImport implements ToModel, WithHeadingRow
{
    private $empresa_id;

    public function __construct(int $empresa_id)
    {
        $this->empresa_id = $empresa_id;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new CatalogoCuenta([
            'empresa_id' => $this->empresa_id,
            'codigo_cuenta' => $row['codigo_cuenta'],
            'nombre_cuenta' => $row['nombre_cuenta'],
            'cuenta_base_id' => null, // Se mapeará después
        ]);
    }
}
