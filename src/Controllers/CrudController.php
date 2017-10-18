<?php

namespace Skvn\Crud\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Routing\Controller;
use League\Flysystem\Exception;
use Skvn\Crud\Form\Form;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Skvn\Crud\Models\CrudNotify as Notify;
use Skvn\Crud\Traits\TooltipTrait;
use Skvn\Crud\Exceptions\ValidationException;
use Skvn\Crud\Exceptions\ConfigException;

class CrudController extends Controller
{
    use TooltipTrait;

    protected $app;
    protected $auth;
    protected $helper;
    protected $cmsHelper;
    protected $request;
    protected $view;

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

    public function welcome()
    {
        return $this->view->make('crud::welcome');
    }

    public function crudIndex($model, $scope = CrudModel :: DEFAULT_SCOPE, $args = [])
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $view = ! empty($args['view']) ? $args['view'] : $obj->resolveView('index');

        return $this->view->make($view, ['crudObj' => $obj, 'crudList' => $obj->getList()]);
    }

//

    public function crudPopupIndex($model, $scope = CrudModel :: DEFAULT_SCOPE, $args = [])
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $view = ! empty($args['view']) ? $args['view'] : $obj->resolveView('popup_index');

        return $this->view->make($view, ['crudObj' => $obj, 'crudList' => $obj->getList()]);
    }

//

    public function crudTree($model, $scope = CrudModel :: DEFAULT_SCOPE)
    {
        $obj = CrudModel :: createInstance($model, $scope);

        if ($this->request->ajax()) {
            $params = $this->request->all();
            $params['search'] = ! empty($params['search']['value']) ? $params['search']['value'] : '';

            return CrudModelCollectionBuilder :: createTree($obj, $params)
                ->applyContextFilter()
                ->fetch();
        }

        return $this->view->make($obj->resolveView('tree'), ['crudObj' => $obj]);
    }

//

    public function crudAutocompleteList($model)
    {
        return $this->crudAutocompleteSelectOptions($model, true);
    }

    public function crudAutocompleteSelectOptions($model, $titlesOnly = false)
    {
        $params = $this->request->all();
        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE);
        $class = CrudModel :: resolveClass($model);

        if (! ($obj->confParam('title_field'))) {
            throw new ConfigException('Unable to init AutocompleteList: title_field is not configured');
        }


        if (method_exists($obj, 'scopeAutocomplete')) {
            $query = $class :: autocomplete($params)->where($obj->confParam('title_field'), 'like', $params['q'].'%');
        } else {
            $query = $class :: where($obj->confParam('title_field'), 'like', $params['q'].'%');
        }

        if ($titlesOnly) {
            return  $query->pluck($obj->confParam('title_field'))->toArray();
        }

        //$attr = property_exists($obj, "autocompleteAttr") ? $obj->autocompleteAttr : $obj->confParam('title_field');
        //var_dump($attr);
        //
        //$res = $obj->getAutocompleteList(\Request::get('q'));
        $res = $query->get();
        $items = [];
        foreach ($res as $v) {
            $items[] = ['id' => $v->getKey(), 'text' => $v->getTitle()];
        }

        return [
            'results' => $items,
        ];
    }

    public function crudList($model, $scope)
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $skip = (int) $this->request->get('start', 0);
        $take = (int) $this->request->get('length', 0);
        $params = $this->request->all();
        $params['search'] = ! empty($params['search']['value']) ? $params['search']['value'] : '';

        return CrudModelCollectionBuilder :: createDataTables($obj, $params)
            ->applyContextFilter()
            ->paginate($skip, $take)
            ->fetch();
    }

//

    public function crudListExcel($model, $scope)
    {
        $obj = CrudModel :: createInstance($model, $scope);

        $params = $this->request->all();
        
        if (method_exists($obj, 'dumpRowsForExcel')) {
            $xls = $obj->dumpRowsForExcel();
        } else {
            $cols = $obj->getList()->getParam('columns');
            $xls = [];
            $row = [];
            foreach ($cols as $col) {
                if ((empty($col['ctype']) || $col['ctype'] != 'checkbox') && $col['data'] != 'actions' && empty($col['invisible'])) {
                    $row[] = $col['title'];
                }
            }
            $xls[] = $row;
    
            $query = $this->app['session']->get('current_query_info');
            if (empty($query) || ! isset($query['sql']) || ! isset($query['bind'])) {
                $q = CrudModelCollectionBuilder :: createDataTables($obj, $params)
                    ->applyContextFilter()->getCollectionQuery()->getQuery();
                $query = ['sql' => $q->toSQL(), 'bind' => $q->getBindings()];
            }
    
            $rs = \DB :: select($query['sql'], $query['bind']);
            foreach ($rs as $r) {
                $row = [];
                foreach ($cols as $col) {
                    if ((empty($col['ctype']) || $col['ctype'] != 'checkbox') && $col['data'] != 'actions' && empty($col['invisible'])) {
                        $row[] = $r[$col['data']] ?? '';
                    }
                }
                $xls[] = $row;
            }
        }

        $writer = new \XLSXWriter();
        $writer->writeSheet($xls, 'Sheet1');
        $data = $writer->writeToString();

        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache'); //keeps ie happy
        header('Content-Disposition: attachment; filename=xls.xlsx');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Length: '.strlen($data));
        header('Content-Transfer-Encoding: binary');
        echo $data;
        exit;
    }

//

    public function crudEdit($model, $id)
    {
        $obj = CrudModel :: createInstance($model, $this->request->get('scope', CrudModel :: DEFAULT_SCOPE), $id);
        $req = $this->request->all();
        //var_dump($req);
        //var_dump($obj->isFillable('parent_id'));

        foreach ($req as $k => $v) {
            if ($obj->isFillable($k)) {
                $obj->setAttribute($k, $v);
            }
        }

        $edit_view = $obj->getScopeParam('edit_tab') ? 'tab' : 'edit';

        return $this->view->make($obj->resolveView($edit_view), ['crudObj' => $obj, 'crudForm' => $obj->getForm(), 'id' => $id, 'scope' => $obj->getScope(), 'form_tabbed' => $obj->getForm()->hasTabs()]);
    }

    public function crudUpdate($model, $id)
    {
        try {
            $obj = CrudModel :: createInstance($model, $this->request->get('scope', CrudModel :: DEFAULT_SCOPE), $id);
            $form = $obj->getForm();

            $form->load($this->request->all());
            $obj->validate(true);
            $data = $this->request->all();
            $this->app['db']->transaction(function () use ($obj, $data) {
                $obj->isTree() ? $obj->saveTree($this->request->all) : $obj->save();
                foreach ($obj->getScopeParam('on_save') ?? [] as $saver) {
                    $obj->$saver($data);
                }
                //$obj->saveRelations();
            });
            $obj->crudSaved();

            return ['success' => true, 'crud_id' => $obj->getKey(), 'crud_model' => $obj->classShortName, 'crud_table' => $obj->classViewName];
        } catch (ValidationException $e) {
            return ['success' => false, 'errors' => $obj->getErrors()];
        } catch (\Exception $e) {
            var_dump($e->getTraceAsString());

            return ['success' => false, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
        }
    }

    public function crudRunValidators()
    {
        $checks = $this->request->get('validates');
        $slugs = [];
        foreach ($checks as $idx => $check) {
            $check['valid'] = true;
            switch ($check['validator']) {
                case 'slug':
                    if (! isset($slugs[$check['model']])) {
                        $slugs[$check['model']] = [];
                    }
                    if (in_array($check['value'], $slugs[$check['model']])) {
                        $check['valid'] = false;
                        $check['error_message'] = 'Значение не уникально';
                    } else {
                        $obj = CrudModel :: createInstance($check['model'], null, $check['id'] > 0 ? $check['id'] : null);
                        $valid = $obj->validateSlug($check['value']);
                        if ($valid < 0) {
                            $check['valid'] = false;
                            $check['error_message'] = $valid === -99 ? 'Неподдерживаемый для URL формат' : 'Значение не уникально';
                        }
                        $slugs[$check['model']][] = $check['value'];
                    }
                break;
            }
            $checks[$idx] = $check;
        }

        return $checks;
    }

    public function crudFilter($model, $scope)
    {
        try {
            $obj = CrudModel :: createInstance($model, $scope);

            $obj->getList()->fillFilter($this->request->all());

            return ['success' => true, 'crud_model' => $obj->classShortName, 'scope' => $scope];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function crudDelete($model)
    {
        try {
            $class = CrudModel :: resolveClass($model);

            $ids = $this->request->get('ids');
            if (is_array($ids)) {
                $class::destroy($ids);
            }
            $obj = new $class();
            $obj->crudDeleted();

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function crudCommand($model, $id, $command)
    {
        try {
            $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE, $id);
            $ret = $obj->crudExecuteCommand(camel_case($command), $this->request->all());

            return ['success' => true, 'ret' => $ret, 'message' => isset($ret['message']) ? $ret['message'] : null];
        } catch (\Exception $e) {
            var_dump($e->getTraceAsString());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function crudTreeMove($model)
    {
        $id = $this->request->get('id');
        $parent_id = $this->request->get('parent_id');
        $position = $this->request->get('position');

        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE, $id);

        $res = $obj->moveTreeAction($parent_id, $position);
        if ($res === true) {
            return ['success' => true, 'crud_model' => $obj->classViewName];
        } else {
            return ['success' => false, 'message' => $res];
        }
    }

    public function crudTableColumns()
    {
        if ($this->app['auth']->check()) {
            $user = $this->app['auth']->user();
            if ($user instanceof \Skvn\Crud\Contracts\PrefSubject) {
                return $user->crudPrefUI(constant(get_class($user).'::PREF_TYPE_COLUMN_LIST'));
            } else {
                return ['success' => true];
            }
        } else {
            return \Response('Access denied', 403);
        }
    }

    public function crudNotifyFetch()
    {
        return Notify :: fetchNext();
    }

    public function crudTreeOptions($model)
    {
        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE, $this->request->get('id'));
        $fObj = $obj->getForm()->getFieldByName($this->request->get('field'));

        return $fObj->getOptions();
    }

    public function crudSearchOptions($model)
    {
        $obj = CrudModel :: createInstance($model, CrudModel :: DEFAULT_SCOPE, $this->request->get('id'));
        $fObj = $obj->getForm()->getFieldByName($this->request->get('field'));

        return $fObj->getSearchOptions($this->request->get('query'));
    }

    public function typoCheck()
    {
        $html = $this->app['request']->get('content');
        $remoteTypograf = new \Skvn\Crud\Helper\RemoteTypograf();

        $remoteTypograf->htmlEntities();
        $remoteTypograf->br(false);
        $remoteTypograf->p(true);
        $remoteTypograf->nobr(3);
        $remoteTypograf->quotA('laquo raquo');
        $remoteTypograf->quotB('bdquo ldquo');

        return $remoteTypograf->processText($html);
    }

    public function typoCheck2()
    {
        $html = $this->app['request']->get('content');
        $fp = fsockopen('www.typograf.ru', 80, $errno, $errstr, 30);

        $data = 'text='.urlencode($html).'&chr=UTF-8';

        if ($fp) {
            fwrite($fp, "POST /webservice/ HTTP/1.1\n");
            fwrite($fp, "Host: www.typograf.ru\n");
            fwrite($fp, "Content-type: application/x-www-form-urlencoded\n");
            fwrite($fp, 'Content-length: '.strlen($data)."\n");
            fwrite($fp, "User-Agent: PHP Script\n");
            fwrite($fp, "Connection: close\n\n");
            fwrite($fp, $data);
            while (fgets($fp, 2048) != "\r\n" && ! feof($fp));
            $buf = '';
            while (! feof($fp)) {
                $buf .= fread($fp, 2048);
            }
            fclose($fp);

            return $buf;
        } else {
            return $html;
        }
    }
}
