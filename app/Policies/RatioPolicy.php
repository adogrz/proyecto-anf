<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RatioPolicy
{
    use HandlesAuthorization;

    /**
     * Ver análisis de ratios financieros
     */
    public function viewAnalysis(?User $user): bool
    {
        // Permitir acceso a usuarios autenticados con permisos específicos
        if (!$user) {
            return false;
        }

        return $user->can('ratios-financieros.index');
    }

    /**
     * Ver gráficas de evolución
     */
    public function viewGraphics(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->can('ratios-financieros.index');
    }

    /**
     * Ver comparación entre empresas
     */
    public function compareCompanies(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->can('ratios-financieros.index');
    }
}
