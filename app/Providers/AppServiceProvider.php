<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route; // Import Route facade
use App\Models\PlantillaCatalogo; // Import PlantillaCatalogo model

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::model('plantillas_catalogo', PlantillaCatalogo::class);
    }
}
