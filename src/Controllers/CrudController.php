<?php namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use League\Flysystem\Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application as LaravelApplication;
use Skvn\Crud\Models\CrudNotify as Notify;
use Skvn\Crud\Models\CrudModel;

class CrudController extends Controller
{
    protected $app,$auth, $helper, $cmsHelper,  $request;


    public function __construct(LaravelApplication $app, Guard $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->helper = $this->app->make('skvn.crud');
        $this->cmsHelper = $this->app->make('skvn.cms');
        $this->request = $this->app['request'];
        \View::share('cmsHelper', $this->cmsHelper);
        \View::share('config', $this->app['config']->get('crud_common'));

    }

    function welcome()
    {
        return $this->app['view']->make("crud::welcome");
    }


    function crudIndex($model, $scope = CrudModel :: DEFAULT_SCOPE, $args = [])
    {
        $obj = $this->helper->getModelInstance($model, $scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $obj->initFilter();

        $view = !empty($args['view']) ? $args['view'] : $this->helper->resolveModelView($obj, 'index');
        return $this->app['view']->make($view, ['crudObj'=>$obj]);

    }//


    function crudTree($model, $scope = CrudModel :: DEFAULT_SCOPE)
    {
        $obj = $this->helper->getModelInstance($model, $scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $obj->initFilter();

        if ($this->app['request']->ajax())
        {
             return $obj->getListData($scope,'tree');

        }
        return $this->app['view']->make($this->helper->resolveModelView($obj,'tree'),['crudObj'=>$obj]);

    }//



    function crudAutocompleteList($model, $scope)
    {
        $obj = $this->helper->getModelInstance($model, $scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        return $obj->getAutocompleteList(\Request::get('q'));
    }

    function crudList($model,$scope)
    {
        $obj = $this->helper->getModelInstance($model, $scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        return $obj->getListData($scope,'data_tables');


    }//

    function crudEdit($model,$id)
    {

        $obj = $this->helper->getModelInstance($model, $this->app['request']->get('scope', CrudModel :: DEFAULT_SCOPE), $id);
        //$class = 'App\Model\\'.studly_case($model);
        //$obj = $class::firstOrNew(['id'=>(int)$id]);
        //$scope = \Input::get('scope', CrudModel :: DEFAULT_SCOPE);
        //$obj->config->setScope($scope);


        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $req = $this->app['request']->all();

        foreach ($req as $k=>$v)
        {

            if ($obj->isFillable($k))
            {
                $obj->setAttribute($k,$v);
            }
        }
        //return \View::make($this->crudHelper->resolveModelTemplate($model,$obj->config->get('tabs') ? 'edit_tabs' : 'edit'),['crudObj'=>$obj,'id'=>$id]);
        $edit_view = $obj->getListConfig('edit_tab')?'tab':'edit';
        return $this->app['view']->make($this->helper->resolveModelView($obj,$edit_view),['crudObj'=>$obj,'id'=>$id,'scope'=>$obj->getScope(), 'form_tabbed'=>$obj->getListConfig('form_tabbed')]);

    }

    function crudUpdate($model,$id)
    {

        try {
            $obj = $this->helper->getModelInstance($model, CrudModel :: DEFAULT_SCOPE, $id);
//            $class = 'App\Model\\' .studly_case($model);
//            $obj = $class::firstOrNew(['id'=>(int)$id]);

            if (!$obj->checkAcl('u'))
            {
                return \Response('Access denied',403);
            }


            if ($obj->isTree())
            {
                $obj->saveTree($this->app['request']->all());
            } else {
                $obj->fillFromRequest($this->app['request']->all());

                if (!$obj->save())
                {
                    return ['success'=>false,'error'=>implode("\n",array_values($obj->getErrors()->all()))];
                }


            }
            return ['success'=>true,'crud_id'=>$obj->id,'crud_model'=>$obj->classShortName, 'crud_table'=> $obj->classViewName ];

        } catch( \Exception $e)
        {

//            var_dump($e)
            return ['success'=>false, 'error'=>$e->getMessage(),'trace' => $e->getTraceAsString()];
        }

    }


    function crudFilter($model,$scope)
    {

        try {
            $obj = $this->helper->getModelInstance($model, $scope);
//            $obj = \App::make('App\Model\\'.studly_case($model));
//            $obj->config->setScope($scope);
            $obj->fillFilter($scope,$this->app['request']->all());

            if (!$obj->checkAcl())
            {
                return \Response('Access denied',403);
            }

            return ['success'=>true,'crud_model'=>$obj->classShortName,'scope'=>$scope];

        } catch(Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }

    function crudDelete($model)
    {
        try {
            $class = $this->helper->getModelClass($model);
            $obj = $this->helper->getModelInstance($model);
//            $model = 'App\Model\\' . studly_case($model);
//            $obj = \App::make($model);

            if (!$obj->checkAcl())
            {
                return \Response('Access denied',403);
            }

            $ids = $this->app['request']->get('ids');
            if (is_array($ids))
            {
                $class::destroy($ids);
            }

            return ['success'=>true];

        } catch(Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }


    function crudCommand($model, $id, $command)
    {

        try {
            $obj = $this->helper->getModelInstance($model, CrudModel :: DEFAULT_SCOPE, $id);

            if (!$obj->checkAcl())
            {
                return \Response('Access denied',403);
            }
            $command = camel_case($command);
            $ret = $obj->$command($this->app['request']->all());

            return ['success'=>true, 'ret'=>$ret, 'message' => isset($ret['message']) ? $ret['message'] : null];

        } catch(\Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    function crudTreeMove($model)
    {
        $id = $this->app['request']->get('id');
        $parent_id = $this->app['request']->get('parent_id');
        $position = $this->app['request']->get('position');

        $obj = $this->helper->getModelInstance($model, CrudModel :: DEFAULT_SCOPE, $id);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $res = $obj->moveTreeAction($parent_id,$position);
        if ($res === true)
        {
            return ['success'=>true, 'crud_model'=>$obj->classViewName];
        } else {
           return ['success'=> false,'message'=>$res];
        }



    }

    function crudTableColumns()
    {
        if ($this->app['auth']->check())
        {
            $user = $this->app['auth']->user();
            if ($user instanceof \Skvn\Crud\Contracts\PrefSubject)
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

    function crudNotifyFetch()
    {
        return Notify :: fetchNext();

    }



}
