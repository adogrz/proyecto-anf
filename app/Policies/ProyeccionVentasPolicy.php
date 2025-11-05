<?php

namespace App\Policies;

use App\Models\ProyeccionVenta;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProyeccionVentasPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('proyecciones.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProyeccionVenta $proyeccionVenta): bool
    {
        return $user->can('proyecciones.index');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('proyecciones.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProyeccionVenta $proyeccionVenta): bool
    {
        return $user->can('proyecciones.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProyeccionVenta $proyeccionVenta): bool
    {
        return $user->can('proyecciones.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProyeccionVenta $proyeccionVenta): bool
    {
        return $user->can('proyecciones.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProyeccionVenta $proyeccionVenta): bool
    {
        return $user->can('proyecciones.delete');
    }
}
