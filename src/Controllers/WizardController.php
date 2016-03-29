<?php  namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use Skvn\Crud\CrudException;
use Skvn\Crud\CrudWizardException;
use Skvn\Crud\Wizard\Migrator;
use Skvn\Crud\Wizard\Wizard;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application as LaravelApplication;

class WizardController extends Controller {



    private $request;
    private $wizard;
    
    function __construct(LaravelApplication $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
        $this->helper = $this->app->make('skvn.crud');
        $this->cmsHelper = $this->app->make('skvn.cms');

        $this->wizard = new Wizard();
        if (!$this->wizard->checkConfigDir())
        {
            view()->share('alert', 'Config directory "'.$this->wizard->config_dir_path.'" is not writable');
        }

        if (!$this->wizard->checkModelsDir())
        {
            view()->share('alert', 'Models directory "'.$this->wizard->model_dir_path.'" is not writable');
        }

        if (!$this->wizard->checkMigrationsDir())
        {
            view()->share('alert', 'Migrations directory "'.base_path() . '/database/migrations" is not writable');
        }


        \View::share('cmsHelper', $this->cmsHelper);
        \View::share('wizard', $this->wizard);
    }

    function index()
    {

        if ($this->request->isMethod('post'))
        {
             return $this->createModels();
        }
        return view('crud::wizard/index', ['wizard'=>$this->wizard]);
    }

    function model($table)
    {
        if ($this->request->isMethod('post'))
        {

            $proto = new CrudModelPrototype($this->request->all());
            $proto->record();

            
            return redirect(route('wizard_index'))->with(['error'=>$proto->error]);

        }


        $model = $this->wizard->getModelConfig($table);
        if (!$model)
        {
            if (!empty($this->request->get('model'))) {

                $mname = studly_case(trim($this->request->get('model')));
                $proto = new CrudModelPrototype(['name' => $mname, 'table' => $table]);
                $proto->record();
                return redirect()->route('wizard_model',[$table]);
            }
        }

        return view('crud::wizard/model', ['table'=>$table,'model'=>$model]);
    }


    function menu()
    {

        return view('crud::wizard/menu');
    }

    function createModels()
    {
        $tables = $this->request->input('models');
        foreach ($tables as $table=>$model)
        {
            if (!empty($model))
            {
                $model = studly_case(trim($model));
                $proto = new CrudModelPrototype(['name'=>$model, 'table'=>$table]);
                $proto->record();
            }
        }

        return redirect()->back();
    }


    function getTableColumns($table)
    {

        return $this->wizard->getTableColumns($table);
    }
    
    function getFieldRowTpl($field_name)
    {
        return view('crud::wizard/blocks/fields/field_row', ['f'=>$field_name]);
    }
    
    function migrationCreate()
    {
        $migrator = new Migrator($this->request);

        if ($migrator->createTable()->migrate())
        {
             return redirect()->back();

        } else {

            return redirect()->back()->with('error', $migrator->error);
        }
    
    }

    function migrationAlter(Request $req)
    {
        $table = $req->get('table_name');
        $columns = $req->get('columns');
        
        if (empty($table) || empty($columns))
        {
            throw new CrudWizardException('No table name or columns specified');
        }

        

        $options = ['name'=>"add_".$table];
        $command = "make:migration:schema";
        \Artisan::call($command, $options);

        \Artisan::call("migrate", ['--force'=>true,'--quiet'=>true]);

        return redirect()->back();

    }


}
