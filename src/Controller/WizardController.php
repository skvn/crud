<?php  namespace Skvn\Crud\Controller;

use Illuminate\Routing\Controller;
use Skvn\Crud\Wizard\Wizard;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Illuminate\Http\Request;

class WizardController extends Controller {



    private $request;
    function __construct(Request $request)
    {
        $this->request = $request;
    }

    function index()
    {

        return view('crud::wizard/index', ['wizard'=>new Wizard()]);
    }

    function model($table)
    {
        $wizard = new Wizard();
        $model = $wizard->getModelConfig($table);
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




}
