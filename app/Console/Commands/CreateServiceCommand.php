<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Override;

class CreateServiceCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';
    protected $type = 'Service';

    protected function getStub()
    {
        return app_path('Console/Commands/Stubs/service.stub');
    }

    #[Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Services';
    }
}
