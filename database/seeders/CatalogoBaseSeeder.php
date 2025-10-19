<?php

namespace Database\Seeders;

use App\Models\CuentaBase;
use App\Models\PlantillaCatalogo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CatalogoBaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            if (PlantillaCatalogo::where('nombre', 'Catálogo General SV')->exists()) {
                $this->command->info('El catálogo base ya ha sido poblado. Omitiendo.');
                return;
            }

            $plantilla = PlantillaCatalogo::create([
                'nombre' => 'Catálogo General SV',
                'descripcion' => 'Catálogo contable base basado en la nomenclatura estándar de El Salvador.',
            ]);

            $filePath = base_path('catalogo.csv');
            $lines = File::lines($filePath);
            $codeToIdMap = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $data = str_getcsv($line);

                if (count($data) !== 2) {
                    $this->command->warn("Línea con formato incorrecto en catalogo.csv y omitida: {$line}");
                    continue;
                }

                $codigo = $data[0];
                $nombre = trim($data[1]);

                // Generic duplicate check
                if (isset($codeToIdMap[$codigo])) {
                    $this->command->warn("Código duplicado encontrado en el catálogo y omitido: [{$codigo}] {$nombre}");
                    continue;
                }

                $isContraAccount = str_ends_with($codigo, 'R');
                $numericCode = rtrim($codigo, 'R');

                $parent_id = null;
                $lastDotPosition = strrpos($numericCode, '.');
                if ($lastDotPosition !== false) {
                    $parentCode = substr($numericCode, 0, $lastDotPosition);
                } else if (strlen($numericCode) > 1) {
                    $parentCode = substr($numericCode, 0, -1);
                } else {
                    $parentCode = null;
                }

                if ($parentCode && isset($codeToIdMap[$parentCode])) {
                    $parent_id = $codeToIdMap[$parentCode];
                }

                $firstDigit = substr($numericCode, 0, 1);
                $naturaleza = in_array($firstDigit, ['2', '3', '5']) ? 'ACREEDORA' : 'DEUDORA';
                if ($isContraAccount) {
                    $naturaleza = ($naturaleza === 'DEUDORA') ? 'ACREEDORA' : 'DEUDORA';
                }

                $cuenta = CuentaBase::create([
                    'plantilla_catalogo_id' => $plantilla->id,
                    'parent_id' => $parent_id,
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'tipo_cuenta' => 'DETALLE', // Default value
                    'naturaleza' => $naturaleza,
                ]);

                $codeToIdMap[$codigo] = $cuenta->id;
            }

            // Second pass to set AGRUPACION type
            $parentIds = CuentaBase::where('plantilla_catalogo_id', $plantilla->id)->whereNotNull('parent_id')->distinct()->pluck('parent_id');
            CuentaBase::whereIn('id', $parentIds)->update(['tipo_cuenta' => 'AGRUPACION']);
        });
    }
}