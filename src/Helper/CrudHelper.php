<?php

namespace Skvn\Crud\Helper;

use Illuminate\Support\Collection;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Exceptions\ConfigException;

class CrudHelper
{
    protected $app;

    public function __construct(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;
    }

    private function flattenKids(&$tree, $node)
    {
        $tree[$node->id] = $node;
        if (is_array($node->kids)) {
            foreach ($node->kids as $kid) {
                $this->flattenKids($tree, $kid);
            }
        }
    }

    public function sortArray($data, $field)
    {
        $field = (array) $field;
        uasort($data, function ($a, $b) use ($field) {
            $retval = 0;
            foreach ($field as $fieldname) {
                if ($retval == 0) {
                    $retval = strnatcmp($a[$fieldname], $b[$fieldname]);
                }
            }

            return $retval;
        });

        return $data;
    }

    public function sortArrayObjects($data, $field)
    {
        $field = (array) $field;
        uasort($data, function ($a, $b) use ($field) {
            $retval = 0;
            foreach ($field as $fieldname) {
                if ($retval == 0) {
                    $retval = strnatcmp($a->$fieldname, $b->$fieldname);
                }
            }

            return $retval;
        });

        return $data;
    }

    public function getSelectOptionsByCollection(Collection $collection, $valueField = 'id', $textField = 'title', $groupField = null, $appendParams = [])
    {
        $opts = [];
        foreach ($collection as $item) {
            if (is_null($groupField)) {
                $opt = ['value' => $item->$valueField, 'text' => $item->$textField];
                foreach ($appendParams as $p) {
                    $opt[$p] = $item->$p;
                }
                $opts[] = $opt;
            } else {
                if (! isset($opts[$item[$groupField]])) {
                    $opts[$item[$groupField]] = ['title' => $item[$groupField], 'options' => []];
                }
                $opt = ['value' => $item[$valueField], 'text' => $item[$textField]];
                foreach ($appendParams as $p) {
                    $opt[$p] = $item[$p];
                }
                $opts[$item[$groupField]]['options'][] = $opt;
            }
        }

        return $opts;
    }
    
    public function getModelAutocompleteList(CrudModel $model, $params = [])
    {
        $class = get_class($model);
        if (! ($model->confParam('title_field'))) {
            throw new ConfigException('Unable to init AutocompleteList for ' . $model->classViewName . ': title_field is not configured');
        }
        if (method_exists($model, 'scopeAutocomplete')) {
            if (method_exists($model, 'autocompleteScopeOnly') && $model->autocompleteScopeOnly()) {
                $query = $class::autocomplete($params);
            } else {
                $query = $class::autocomplete($params)->where($model->confParam('title_field'), 'like', $params['q'] . '%');
            }
        } else {
            $query = $class::where($model->confParam('title_field'), 'like', $params['q'].'%');
        }
        if (!empty($params['titlesOnly'])) {
            return  $query->pluck($model->confParam('title_field'))->toArray();
        }
        $res = $query->get();
        $items = [];
        foreach ($res as $v) {
            $items[] = ['id' => $v->getKey(), 'text' => $v->getTitle()];
        }
        return $items;
    }
}
