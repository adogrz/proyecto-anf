<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstadoFinancieroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Por ahora, permitimos que cualquier usuario autenticado realice esta solicitud.
        // En una aplicación real, aquí se implementarían controles de autorización más granulares.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'anio' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 50)], // Año actual + 50 años
            'tipo_estado' => ['required', 'string', 'in:balance_general,estado_resultados'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id' => ['nullable', 'exists:detalles_estados,id'], // Para detalles existentes
            'detalles.*.catalogo_cuenta_id' => ['required', 'exists:catalogos_cuentas,id'],
            'detalles.*.valor' => ['required', 'numeric', 'min:0'], // Asumiendo valores no negativos, ajustar si se permiten negativos
        ];
    }
}
