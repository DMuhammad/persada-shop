<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Override;

class CreateRepositoryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';
    protected $type = 'Repository';

    #[Override]
    protected function getStub()
    {
        return app_path('Console/Commands/Stubs/repository.stub');
    }

    #[Override]
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Repositories';
    }
}
