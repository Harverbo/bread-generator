<?php

namespace Harverbo\BreadGenerator;

use Illuminate\Support\ServiceProvider;

class BreadGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            BreadStubGenerator::class,
            BreadRowsGenerator::class
        ]);
    }
}
