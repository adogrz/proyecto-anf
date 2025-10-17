"""<?php

namespace Database\Seeders;

use App\Models\CuentaBase;
use App\Models\PlantillaCatalogo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CatalogoBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create the master template
            $plantilla = PlantillaCatalogo::create([
                'nombre' => 'Catálogo General SV',
                'descripcion' => 'Catálogo contable base basado en la nomenclatura estándar de El Salvador.',
            ]);

            $filePath = base_path('catalogo.txt');
            $lines = File::lines($filePath);

            $codeToIdMap = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // 2. Parse code and name
                preg_match('/^([\d\.]+R?)\s+(.*)$/', $line, $matches);
                if (count($matches) !== 3) {
                    continue;
                }

                $codigo = $matches[1];
                $nombre = trim($matches[2]);
                $isContraAccount = str_ends_with($codigo, 'R');
                $numericCode = rtrim($codigo, 'R');

                // 3. Determine parent
                $parent_id = null;
                $lastDotPosition = strrpos($numericCode, '.');
                if ($lastDotPosition !== false) {
                    $parentCode = substr($numericCode, 0, $lastDotPosition);
                    if (isset($codeToIdMap[$parentCode])) {
                        $parent_id = $codeToIdMap[$parentCode];
                    }
                } else if (strlen($numericCode) > 1) {
                    $parentCode = substr($numericCode, 0, -1);
                     if (isset($codeToIdMap[$parentCode])) {
                        $parent_id = $codeToIdMap[$parentCode];
                    }
                }


                // 4. Determine nature
                $firstDigit = substr($numericCode, 0, 1);
                $naturaleza = 'DEUDORA'; // Default
                if (in_array($firstDigit, ['2', '3', '5'])) {
                    $naturaleza = 'ACREEDORA';
                }
                // Flip nature for contra-accounts
                if ($isContraAccount) {
                    $naturaleza = ($naturaleza === 'DEUDORA') ? 'ACREEDORA' : 'DEUDORA';
                }

                // For now, we'll classify everything as DETALLE. A second pass could update to AGRUPACION.
                // This is simpler and safer for a first run.
                $tipo_cuenta = 'DETALLE';


                $cuenta = CuentaBase::create([
                    'plantilla_catalogo_id' => $plantilla->id,
                    'parent_id' => $parent_id,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'tipo_cuenta' => $tipo_cuenta,
                    'naturaleza' => $naturaleza,
                ]);

                $codeToIdMap[$codigo] = $cuenta->id;
            }

            // Second pass to update 'tipo_cuenta' to AGRUPACION
            $cuentasConHijos = CuentaBase::whereIn('id', function ($query) {
                $query->select('parent_id')->from('cuentas_base')->whereNotNull('parent_id');
            })->get();

            foreach($cuentasConHijos as $cuenta) {
                $cuenta->tipo_cuenta = 'AGRUPACION';
                $cuenta->save();
            }
        });
    }
}
""