<?php

namespace LaravelCrud\Commands;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Composer;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CrudTreeCommand extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'crud:tree';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crud make model a tree command';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;


    /**
     * @var Composer
     */
    private $composer;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {

        $this->makeMigration();
        $this->makeConfig();
        $this->makeModel();

    }

    /**
     * Generate the desired migration.
     */
    protected function makeMigration()
    {
        $table = $this->argument('table');
        $path = $this->getPath($table);
        $this->makeDirectory($path);
        $this->files->put($path, $this->compileMigrationStub());
        $this->info('Migration created successfully.');
        $this->composer->dumpAutoloads();

        //migrate
        $this->call('migrate');
    }


    /**
     * Makes config ammends if it exists.
     */
    protected function makeConfig()
    {
        $path = $this->getConfigPath($this->argument('table'));

        if (!$this->files->exists($path)) {
            $this->error('Config '.$path.' not found. Please change it manually');
            return;
        }

        $stub = $this->files->get($path);
        if (preg_match("#(return.+\[)#siUm",$stub, $m)) {
            if (!empty($m[1])) {
                $stub = str_replace($m[1], "return [\n    'tree' => 1,\n    'tree_level_column' => 'tree_depth',\n    'tree_path_column' => 'tree_path',\n", $stub);
            }
            $this->files->put($path, $stub);
        }

    }



    /**
     * Generate an Eloquent model, if the user wishes.
     */
    protected function makeModel()
    {
        $modelPath = $this->getModelPath($this->getModelName());

        if ($this->option('model') && !$this->files->exists($modelPath)) {
            $this->call('make:model', [
                'name' => $this->getModelName()
            ]);
        }

        if ($this->files->exists($modelPath))
        {
            //$obj = App
            var_dump($this->getAppNamespace());
        }
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getModelPath($name)
    {
        $name = str_replace($this->getAppNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Get the class name for the Eloquent model generator.
     *
     * @return string
     */
    protected function getModelName()
    {
        return ucwords(str_singular(camel_case($this->argument('table'))));
    }

    /**
     * Get the path to where we should store the migration.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name)
    {
        return base_path() . '/database/migrations/' . date('Y_m_d_His') . '_make_tree_' . str_singular($name) . '.php';
    }


    /**
     * Get the path to the model crud config
     *
     * @param  string $name
     * @return string
     */
    protected function getConfigPath($name)
    {
        return base_path() . '/config/crud/crud_'.$name.'.php';
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }



    /**
     * Compile the migration stub.
     *
     * @return string
     */
    protected function compileMigrationStub()
    {
        $stub = $this->files->get(__DIR__ . '/../stubs/migrations/make_tree.stub');
        $stub = str_replace('{{table}}',$this->argument('table'),$stub);
        $stub = str_replace('{{model}}',$this->getModelName(),$stub);

        return $stub;
    }


    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['table', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', null, InputOption::VALUE_OPTIONAL, 'Want a model for this table?', true],
        ];
    }
}
