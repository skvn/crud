<?php namespace Skvn\Crud\Helper;

use Illuminate\Support\Collection;
use Skvn\Crud\Models\CrudModel;

class CrudHelper {

    protected $app;


    function __construct(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;
    }

    private function flattenKids(& $tree, $node )
    {
        $tree[$node->id] = $node;
        if (is_array($node->kids))
        {
            foreach ($node->kids as $kid)
            {
                $this->flattenKids($tree, $kid);
            }
        }

    }

    function sortArray( $data, $field )
    {
        $field = (array) $field;
        uasort( $data, function($a, $b) use($field) {
            $retval = 0;
            foreach( $field as $fieldname ) {
                if( $retval == 0 ) $retval = strnatcmp( $a[$fieldname], $b[$fieldname] );
            }
            return $retval;
        } );
        return $data;
    }

    function sortArrayObjects( $data, $field )
    {
        $field = (array) $field;
        uasort( $data, function($a, $b) use($field) {
            $retval = 0;
            foreach( $field as $fieldname ) {
                if( $retval == 0 ) $retval = strnatcmp( $a->$fieldname, $b->$fieldname);
            }
            return $retval;
        } );
        return $data;
    }

    function getSelectOptionsByCollection(Collection $collection, $valueField='id', $textField='title', $groupField = null)
    {
        $opts = [];
        foreach ($collection as $item)
        {
            if (is_null($groupField))
            {
                $opts[] = ['value' => $item->$valueField, 'text' => $item->$textField];
            }
            else
            {
                if (!isset($opts[$item[$groupField]]))
                {
                    $opts[$item[$groupField]] = ['title' => $item[$groupField], 'options' => []];
                }
                $opts[$item[$groupField]]['options'][] = ['value' => $item[$valueField], 'text' => $item[$textField]];
            }
        }

        return $opts;
    }



}
