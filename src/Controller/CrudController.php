<?php namespace LaravelCrud\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use League\Flysystem\Exception;
use Illuminate\Contracts\Auth\Guard;

class CrudController extends Controller {



    protected $auth;
    protected  $crudHelper;


    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
        $this->crudHelper = \App::make('CrudHelper');

    }

    function welcome()
    {
        return \View :: make("crud::welcome");
    }


    function index($model)
    {

        $obj = \App::make('App\Model\\'.studly_case($model));

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $obj->initFilter();

        return \View::make($this->crudHelper->resolveModelTemplate($model,'index'),['crudObj'=>$obj]);

    }//


    function tree($model)
    {

        $obj = \App::make('App\Model\\'.studly_case($model));

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $obj->initFilter();

        if (\Request::ajax())
        {
            $viewTpl = $this->crudHelper->resolveModelTemplate($model,'crud.tree_line');
        } else {
            $viewTpl = $this->crudHelper->resolveModelTemplate($model,'tree');
        }
        return \View::make($viewTpl,
                [
                    'crudObj'=>$obj,
                    'collection'=>$obj->getListData(null,'tree')
                ]);

    }//




    function clist($model,$list)
    {

        $obj = \App::make('App\Model\\'.studly_case($model));
        $context = \Input::get('list_context');
        if (!empty($context))
        {
            $obj->config->setContext($context);
        }

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        return $obj->getListData($list,'data_tables');


    }//

    function edit($model,$id)
    {
        $class = 'App\Model\\'.studly_case($model);
        $obj = $class::firstOrNew(['id'=>(int)$id]);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        return \View::make($this->crudHelper->resolveModelTemplate($model,$obj->config->get('tabs') ? 'edit_tabs' : 'edit'),['crudObj'=>$obj,'id'=>$id]);

    }

    function update($model,$id)
    {

        try {
            $class = 'App\Model\\' .studly_case($model);
            $obj = $class::firstOrNew(['id'=>(int)$id]);

            if (!$obj->checkAcl('u'))
            {
                return \Response('Access denied',403);
            }


            if ($obj->isTree())
            {
                $obj->saveTree(\Input::all());
            } else {
                $obj->fillFromRequest(\Input::all());

                if (!$obj->save())
                {
                    return ['success'=>false,'error'=>implode("\n",array_values($obj->getErrors()->all()))];
                }


            }
            return ['success'=>true,'crud_id'=>$obj->id,'crud_model'=>$obj->classShortName];

        } catch( \Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }


    function filter($model,$context)
    {

        try {
            $obj = \App::make('App\Model\\'.studly_case($model));
            $obj->fillFilter($context,\Input::all());

            if (!$obj->checkAcl())
            {
                return \Response('Access denied',403);
            }

            return ['success'=>true,'crud_model'=>$obj->classShortName,'context'=>$context];

        } catch(Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }

    function delete($model)
    {
        try {
            $model = 'App\Model\\' . studly_case($model);
            $obj = \App::make($model);

            if (!$obj->checkAcl())
            {
                return \Response('Access denied',403);
            }

            $ids = \Request::get('ids');
            if (is_array($ids))
            {
                $model::destroy($ids);
            }

            return ['success'=>true];

        } catch(Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }


    function command($model, $id, $command)
    {

        try {
            $model = 'App\Model\\' . studly_case($model);
            $obj = $model::findOrNew((int)$id);

            if (!$obj->checkAcl())
            {
                return \Response('Access denied',403);
            }
            $command = camel_case($command);
            $ret = $obj->$command(\Input::all());

            return ['success'=>true, 'ret'=>$ret];

        } catch(\Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    function treeMove($model)
    {
        $id = \Input::get('self_id');
        $rel_id = \Input::get('rel_id');
        $command = \Input::get('command');

        $model = 'App\Model\\'.studly_case($model);
        $obj = $model::findOrFail((int)$id);


        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $res = $obj->moveTreeAction($command, $rel_id);
        if ($res === true)
        {
            return ['success'=>true];
        } else {
           return ['success'=> false,'message'=>$res];
        }



    }

    function crudTableColumns()
    {
        if (\Auth :: check())
        {
            $user = \Auth :: user();
            if ($user instanceof \LaravelCrud\Contracts\PrefSubject)
            {
                return $user->crudPrefUI(constant(get_class($user) . '::PREF_TYPE_COLUMN_LIST'));
            }
            else
            {
                return ['success' => true];
            }
        }
        else
        {
            return \Response('Access denied', 403);
        }
    }



}
