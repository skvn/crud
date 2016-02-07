<?php  namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use Skvn\Crud\Wizard\Wizard;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Illuminate\Http\Request;

class WizardController extends Controller {



    private $request;
    function __construct(Request $request)
    {
        $this->request = $request;
        $wizard = new Wizard();
        if (!$wizard->checkConfigDir())
        {
            view()->share('alert', 'Config directory "'.$wizard->config_dir_path.'" is not writable');
        }

        if (!$wizard->checkModelsDir())
        {
            view()->share('alert', 'Models directory "'.$wizard->model_dir_path.'" is not writable');
        }
    }

    function index()
    {

        if ($this->request->isMethod('post'))
        {
             return $this->createModels();
        }
        return view('crud::wizard/index', ['wizard'=>new Wizard()]);
    }

    function model($table)
    {
        if ($this->request->isMethod('post'))
        {

            $proto = new CrudModelPrototype($this->request->all());
            $proto->record();

            return redirect(route('wizard_index'));

        }

        $wizard = new Wizard();
        $model = $wizard->getModelConfig($table);
        if (!$model)
        {
            if (!empty($this->request->get('model'))) {

                $mname = studly_case(trim($this->request->get('model')));
                $proto = new CrudModelPrototype(['name' => $mname, 'table' => $table]);
                $proto->record();
                return redirect()->route('wizard_model',[$table]);
            }
        }

        return view('crud::wizard/model', ['wizard'=>$wizard,'table'=>$table,'model'=>$model]);
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
        $wizard = new Wizard();
        return $wizard->getTableColumns($table);
    }



}
