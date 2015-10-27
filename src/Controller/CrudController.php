<?php namespace LaravelCrud\Controller;

use Illuminate\Routing\Controller;
use League\Flysystem\Exception;
use Illuminate\Contracts\Auth\Guard;
use LaravelCrud\Model\CrudNotify as Notify;
use LaravelCrud\CrudConfig;

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


    function crudIndex($model, $scope = CrudConfig :: DEFAULT_SCOPE, $args = [])
    {

        $obj = \App::make('App\Model\\'.studly_case($model));
        $obj->config->setScope($scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $obj->initFilter();

        $view = !empty($args['view']) ? $args['view'] : $this->crudHelper->resolveModelView($obj, 'index');
        return \View::make($view, ['crudObj'=>$obj]);

    }//


    function crudTree($model)
    {

        $obj = \App::make('App\Model\\'.studly_case($model));

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        $obj->initFilter();

        if (\Request::ajax())
        {
             return ['data'=>$obj->getListData(\Input::get('scope'),'tree')];

        } else {

        }
        return \View::make($this->crudHelper->resolveModelView($obj,'tree'),
                [
                    'crudObj'=>$obj
                ]);

    }//




    function crudList($model,$scope)
    {

        $obj = \App::make('App\Model\\'.studly_case($model));
        $obj->config->setScope($scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        return $obj->getListData($scope,'data_tables');


    }//

    function crudEdit($model,$id)
    {
        $class = 'App\Model\\'.studly_case($model);
        $obj = $class::firstOrNew(['id'=>(int)$id]);
        $scope = \Input::get('scope', $scope = CrudConfig :: DEFAULT_SCOPE);
        $obj->config->setScope($scope);

        if (!$obj->checkAcl())
        {
            return \Response('Access denied',403);
        }

        //return \View::make($this->crudHelper->resolveModelTemplate($model,$obj->config->get('tabs') ? 'edit_tabs' : 'edit'),['crudObj'=>$obj,'id'=>$id]);
        $edit_view = $obj->config->getList('edit_tab')?'tab':'edit';
        return \View::make($this->crudHelper->resolveModelView($obj,$edit_view),['crudObj'=>$obj,'id'=>$id,'scope'=>$scope, 'form_tabbed'=>$obj->config->getList('form_tabbed')]);

    }

    function crudUpdate($model,$id)
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
            var_dump($e);
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }


    function crudFilter($model,$scope)
    {

        try {
            $obj = \App::make('App\Model\\'.studly_case($model));
            $obj->config->setScope($scope);
            $obj->fillFilter($scope,\Input::all());

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


    function crudCommand($model, $id, $command)
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

            return ['success'=>true, 'ret'=>$ret, 'message' => isset($ret['message']) ? $ret['message'] : null];

        } catch(\Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    function crudTreeMove($model)
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

    function crudNotifyFetch()
    {
        return Notify :: fetchNext();

    }



}
