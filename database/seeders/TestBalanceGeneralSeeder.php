<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\PlantillaCatalogo;
use App\Models\EstadoFinanciero;
use App\Models\DetalleEstado;
use App\Models\CatalogoCuenta;
use App\Services\CatalogoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TestBalanceGeneralSeeder extends Seeder
{
    protected $catalogoService;

    public function __construct(CatalogoService $catalogoService)
    {
        $this->catalogoService = $catalogoService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Ensure CatalogoBaseSeeder has run to populate CuentaBase
            $this->call(CatalogoBaseSeeder::class);

            // Find the default PlantillaCatalogo created by CatalogoBaseSeeder
            $plantillaCatalogo = PlantillaCatalogo::where('nombre', 'Catálogo General SV')->first();

            if (!$plantillaCatalogo) {
                $this->command->error('Plantilla "Catálogo General SV" no encontrada. Asegúrate de que CatalogoBaseSeeder se ejecuta correctamente.');
                return;
            }

            // 2. Create a test Empresa
            $empresa = Empresa::firstOrCreate(
                ['nombre' => 'Empresa de Prueba para Balance'],
                [
                    'sector_id' => 1, // Assuming sector_id 1 exists
                    'plantilla_catalogo_id' => $plantillaCatalogo->id,
                ]
            );

            $this->command->info("Empresa de prueba creada/encontrada: {$empresa->nombre} (ID: {$empresa->id})");

            // 3. Import CatalogoCuenta for this Empresa using catalogo.csv
            $filePath = base_path('catalogo.csv');
            if (!file_exists($filePath)) {
                $this->command->error("catalogo.csv no encontrado en: {$filePath}");
                return;
            }

            // Create a dummy UploadedFile for the service
            $uploadedFile = new UploadedFile(
                $filePath,
                'catalogo.csv',
                'text/csv',
                null,
                true
            );

            $this->catalogoService->importarCuentasBase($uploadedFile, $plantillaCatalogo->id, $empresa->id);
            $this->command->info("Catálogo de cuentas importado para la empresa {$empresa->nombre}.");

            // 4. Create a sample EstadoFinanciero (Balance General)
            $estadoFinanciero = EstadoFinanciero::firstOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'anio' => 2023,
                    'tipo_estado' => 'balance_general',
                ],
                [
                    // Add any other default values if necessary
                ]
            );
            $this->command->info("Estado Financiero (Balance General) creado/encontrado para {$empresa->nombre} (ID: {$estadoFinanciero->id}).");

            // 5. Create sample DetalleEstado records
            // Fetch some CatalogoCuenta records that were just created for this empresa
            $catalogoCuentas = CatalogoCuenta::where('empresa_id', $empresa->id)
                                            // ->whereNotNull('cuenta_base_id') // Removed this filter
                                            ->with('cuentaBase') // Eager load cuentaBase to get naturaleza
                                            ->get();

            $sampleDetails = [
                'ACTIVO' => [
                    'Caja General' => 50000,
                    'Caja Chica' => 500, // Added
                    'Banco Agrícola' => 150000,
                    'Banco Citibank' => 100000, // Added
                    'Clientes' => 75000,
                    'Documentos Por Cobrar' => 20000, // Added
                    'Decoración' => 100000,
                    'Limpieza' => 5000, // Added
                    'Terrenos' => 500000, // Added
                ],
                'PASIVO' => [
                    'Proveedores Locales' => -80000,
                    'Proveedores del Exterior' => -30000, // Added
                    'Préstamos Bancarios Corto Plazo' => -120000,
                    'ISSS' => -5000, // Added
                    'Préstamos Hipotecarios a Largo Plazo' => -200000, // Added
                ],
                'PATRIMONIO' => [
                    'Capital Social Mínimo' => -100000,
                    'Reserva Legal' => -20000, // Added
                    'Utilidades de Ejercicios Anteriores' => -50000, // Added
                    'Utilidad del Ejercicio' => -75000,
                ],
            ];

            foreach ($sampleDetails as $groupName => $accounts) {
                foreach ($accounts as $accountName => $value) {
                    $catalogoCuenta = $catalogoCuentas->first(function ($cc) use ($accountName) {
                        return $cc->nombre_cuenta === $accountName;
                    });

                    if ($catalogoCuenta) {
                        DetalleEstado::firstOrCreate(
                            [
                                'estado_financiero_id' => $estadoFinanciero->id,
                                'catalogo_cuenta_id' => $catalogoCuenta->id,
                            ],
                            [
                                'valor' => $value,
                            ]
                        );
                        $this->command->info("Detalle de estado creado para {$accountName}: {$value}");
                    } else {
                        $this->command->warn("CatalogoCuenta '{$accountName}' no encontrada para crear detalle.");
                    }
                }
            }
            $this->command->info("Datos de Balance General de prueba generados exitosamente.");
        });
    }
}