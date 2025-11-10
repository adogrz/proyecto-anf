<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CuentasBaseImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        // This class is a simple collector.
        // The processing logic will be handled by a service
        // to respect the existing architecture.
    }
}
