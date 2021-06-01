<?php

namespace Harverbo\BreadGenerator;

use Illuminate\Support\ServiceProvider;
use Harverbo\BreadGenerator\Console\Commands\BreadRowsGenerator;
use Harverbo\BreadGenerator\Console\Commands\BreadStubGenerator;

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
