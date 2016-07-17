<?php namespace Skvn\Crud\Controllers;

use Illuminate\Routing\Controller;
use League\Flysystem\Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application as LaravelApplication;
use Skvn\Crud\Models\CrudNotify as Notify;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Skvn\Crud\Traits\TooltipTrait;
use Skvn\Crud\Form\Form;

class CrudController extends Controller
{
    use TooltipTrait;

    protected $app,$auth, $helper, $cmsHelper,  $request, $view;


    public function __construct(LaravelApplication $app, Guard $auth)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->helper = $this->app->make('skvn.crud');
        $this->cmsHelper = $this->app->make('skvn.cms');
        $this->request = $this->app['request'];
        $this->view = $this->app['view'];
        $this->view->share('cmsHelper', $this->cmsHelper);
        $this->view->share('config', $this->app['config']->get('crud_common'));
        $this->view->share('avail_controls', Form :: getAvailControls());

    }

    function welcome()
    {
        return $this->view->make("crud::welcome");
    }


    function crudIndex($model, $scope = CrudModel :: DEFAULT_SCOPE, $args = [])
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $view = !empty($args['view']) ? $args['view'] : $obj->resolveView('index');
        return $this->view->make($view, ['crudObj'=>$obj, 'crudList' => $obj->getList()]);

    }//

    function crudPopupIndex($model, $scope = CrudModel :: DEFAULT_SCOPE, $args = [])
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $view = !empty($args['view']) ? $args['view'] : $obj->resolveView('popup_index');
        return $this->view->make($view, ['crudObj'=>$obj, 'crudList' => $obj->getList()]);

    }//


    function crudTree($model, $scope = CrudModel :: DEFAULT_SCOPE)
    {
        $obj = CrudModel :: createInstance($model, $scope);

        if ($this->request->ajax())
        {
            $params = $this->request->all();
            $params['search'] = !empty($params['search']['value']) ? $params['search']['value'] : '';
            return CrudModelCollectionBuilder :: createTree($obj, $params)
                ->applyContextFilter()
                ->fetch();

        }
        return $this->view->make($obj->resolveView('tree'),['crudObj'=>$obj]);

    }//



    function crudAutocompleteList($model)
    {
        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE);
        return array_values($obj->getAutocompleteList(\Request::get('q')));
    }

    function crudAutocompleteSelectOptions($model)
    {
        $params = $this->request->all();
        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE);
        $class = CrudModel :: resolveClass($model);

        if (!($obj->confParam('title_field')))
        {
            throw new CrudException('Unable to init AutocompleteList: title_field is not configured');
        }


        if (method_exists($obj, 'scopeAutocomplete'))
        {
            $query = $class :: autocomplete($params)->where($obj->confParam('title_field'), 'like', $params['q'] . '%');
        }
        else
        {
            $query = $class :: where($obj->confParam('title_field'), 'like', $params['q'] . '%');
        }
        $res = $query->pluck($obj->confParam('title_field'), $obj->getKeyName())->toArray();
        //$res = $obj->getAutocompleteList(\Request::get('q'));
        $items = [];
        foreach ($res as $k=>$v)
        {
            $items[] = ['id'=>$k,'text'=>$v];
        }
        return [
            'results' =>$items
        ];
    }

    function crudList($model, $scope)
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $skip = (int) $this->request->get('start',0);
        $take =  (int) $this->request->get('length',0);
        $params = $this->request->all();
        $params['search'] = !empty($params['search']['value']) ? $params['search']['value'] : '';

        return CrudModelCollectionBuilder :: createDataTables($obj, $params)
            ->applyContextFilter()
            ->paginate($skip, $take)
            ->fetch();
    }//

    function crudListExcel($model, $scope)
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $params = $this->request->all();

        $cols = $obj->getList()->getParam("columns");
        $xls = [];
        $row = [];
        foreach ($cols as $col)
        {
            if ((empty($col['ctype']) || $col['ctype'] != "checkbox") && $col['data'] != "actions" && empty($col['invisible']))
            {
                $row[] = $col['title'];
            }
        }
        $xls[] = $row;

        $query = $this->app['session']->get("current_query_info");
        if (empty($query) || !isset($query['sql']) || !isset($query['bind']))
        {
            $q = CrudModelCollectionBuilder :: createDataTables($obj, $params)
                ->applyContextFilter()->getCollectionQuery()->getQuery();
            $query = ['sql' => $q->toSQL(), 'bind' => $q->getBindings()];
        }

        $rs = \DB :: select($query['sql'], $query['bind']);
        foreach ($rs as $r)
        {
            $row = [];
            foreach ($cols as $col)
            {
                if ((empty($col['ctype']) || $col['ctype'] != "checkbox") && $col['data'] != "actions" && empty($col['invisible']))
                {
                    $row[] = $r[$col['data']] ?? "";
                }
            }
            $xls[] = $row;
        }



        $writer = new \XLSXWriter();
        $writer->writeSheet($xls,'Sheet1');
        $data = $writer->writeToString();

        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache"); //keeps ie happy
        header("Content-Disposition: attachment; filename=xls.xlsx");
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Length: " . strlen($data));
        header('Content-Transfer-Encoding: binary');
        echo $data;
        exit;
    }//

    function crudEdit($model,$id)
    {

        $obj = CrudModel :: createInstance($model, $this->request->get('scope', CrudModel :: DEFAULT_SCOPE), $id);
        $req = $this->request->all();

        foreach ($req as $k=>$v)
        {

            if ($obj->isFillable($k))
            {
                $obj->setAttribute($k,$v);
            }
        }

        $edit_view = $obj->scopeParam('edit_tab')?'tab':'edit';
        return $this->view->make($obj->resolveView($edit_view),['crudObj'=>$obj, 'crudForm' => $obj->getForm(), 'id'=>$id,'scope'=>$obj->getScope(), 'form_tabbed'=>$obj->getForm()->hasTabs()]);

    }

    function crudUpdate($model,$id)
    {

        try {
            $obj = CrudModel :: createInstance($model, $this->request->get('scope', CrudModel :: DEFAULT_SCOPE), $id);
            $form = $obj->getForm();

            $form->load($this->request->all());
            if ($obj->isTree())
            {
                $obj->saveTree($this->request->all());
            } else {
                //$obj->fillFromRequest($this->request->all());

                if (!$obj->save())
                {
                    return ['success'=>false,'error'=>implode("\n",array_values($obj->getErrors()))];
                }


            }
            return ['success'=>true,'crud_id'=>$obj->id,'crud_model'=>$obj->classShortName, 'crud_table'=> $obj->classViewName ];

        } catch( \Exception $e)
        {

            var_dump($e->getTraceAsString());
            return ['success'=>false, 'error'=>$e->getMessage(),'trace' => $e->getTraceAsString()];
        }

    }


    function crudFilter($model,$scope)
    {

        try {
            $obj = CrudModel :: createInstance($model, $scope);

            $obj->getList()->fillFilter($this->request->all());

            return ['success'=>true,'crud_model'=>$obj->classShortName,'scope'=>$scope];

        } catch(Exception $e)
        {
            return ['success'=>false, 'error'=>$e->getMessage()];
        }

    }

    function crudDelete($model)
    {
        try {
            $class = CrudModel :: resolveClass($model);

            $ids = $this->request->get('ids');
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
            $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE, $id);
            $ret = $obj->crudExecuteCommand(camel_case($command), $this->request->all());
            return ['success'=>true, 'ret'=>$ret, 'message' => isset($ret['message']) ? $ret['message'] : null];

        } catch(\Exception $e)
        {
            var_dump($e->getTraceAsString());
            return ['success'=>false, 'error'=>$e->getMessage()];
        }
    }

    function crudTreeMove($model)
    {
        $id = $this->request->get('id');
        $parent_id = $this->request->get('parent_id');
        $position = $this->request->get('position');

        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE, $id);

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


    function crudTreeOptions($model)
    {
        $obj = CrudModel :: createInstance($model,CrudModel :: DEFAULT_SCOPE, $this->request->get('id'));
        $fObj = $obj->getForm()->getFieldByName($this->request->get('field'));
        return $fObj->getOptions();

    }

    function crudSearchOptions($model)
    {
        $obj = CrudModel :: createInstance($model,CrudModel :: DEFAULT_SCOPE, $this->request->get('id'));
        $fObj = $obj->getForm()->getFieldByName($this->request->get('field'));
        return $fObj->getSearchOptions($this->request->get('query'));

    }

    function typoCheck()
    {
        $html = $this->app['request']->get('content');
        $remoteTypograf = new \Skvn\Crud\Helper\RemoteTypograf();

        $remoteTypograf->htmlEntities();
        $remoteTypograf->br(false);
        $remoteTypograf->p(true);
        $remoteTypograf->nobr(3);
        $remoteTypograf->quotA ('laquo raquo');
        $remoteTypograf->quotB ('bdquo ldquo');

        return $remoteTypograf->processText($html);
    }

    function typoCheck2()
    {
        $html = $this->app['request']->get('content');
        $fp = fsockopen('www.typograf.ru',80,$errno, $errstr, 30 );

        $data = 'text='.urlencode($html).'&chr=UTF-8';

        if ($fp) {
            fputs($fp, "POST /webservice/ HTTP/1.1\n");
            fputs($fp, "Host: www.typograf.ru\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
            fputs($fp, "Content-length: " . strlen($data) . "\n");
            fputs($fp, "User-Agent: PHP Script\n");
            fputs($fp, "Connection: close\n\n");
            fputs($fp, $data);
            while(fgets($fp,2048) != "\r\n" && !feof($fp));
            $buf = "";
            while(!feof($fp)) $buf .= fread($fp,2048);
            fclose($fp);
            return $buf;
        }
        else
        {
            return $html;
        }
    }

}
