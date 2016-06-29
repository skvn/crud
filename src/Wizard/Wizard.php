<?php namespace Skvn\Crud\Wizard;

use Skvn\Crud\Form\Form;
use Skvn\Crud\Form\Field;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Contracts\FormControlFilterable;


/**
 * Class Wizard
 * @package Skvn\Crud
 * @author Vitaly Nikolenko <vit@webstandart.ru>
 */
class Wizard
{


    /**
     * @var null bool|null
     */
    private $is_models_defined = null;
    /**
     * @var
     */
    private $app;
    /**
     * @var
     */
    private $model_configs;
    /**
     * @var
     */
    private $available_models;
    /**
     * @var
     */
    private $table_columns;

    /**
     * @var
     */
    private $table_int_columns;

    /**
     * @var
     */

    private $crud_configs;
    /**
     * @var
     */
    private $table_column_types;

    /**
     * @var string Path to crud config storage directory
     */
    public $config_dir_path = '';

    /**
     * @var string Path to model directory
     */
    public $model_dir_path = '';

    /*
     * @var array array of tables not showing in wizard model list
     */
    private $skip_tables = ['users','password_resets','migrations'];


    /**
     * Wizard constructor.
     */
    function __construct()
    {
        $this->app = app();
        $this->config_dir_path =  config_path('crud');

        $folderExpl = explode('\\',$this->app['config']['crud_common.model_namespace']);
        $folder = $folderExpl[(count($folderExpl)-1)];
        $this->model_dir_path = app_path($folder);
    }


    /**
     * Run all checks
     *
     */
    public  function getCheckAlert($model=null)
    {
//        if (!$this->checkConfigDir())
//        {
//           return 'Config directory "'.$this->config_dir_path.'" is not writable';
//        }

        if (!$this->checkConfigDir())
        {
             return 'Config directory "'.$this->config_dir_path.'" is not writable';
        }

        if (!$this->checkModelsDir())
        {
            return 'Models directory "'.$this->model_dir_path.'" is not writable';
        }

        if (!$this->checkMigrationsDir())
        {
            return 'Migrations directory "'.base_path() . '/database/migrations" is not writable';
        }

        if ($model) {
            if (!$this->checkUnsupportedConfig($model)) {
                return 'Model config contains data which is not supported yet by the Wizard';
            }
        }



    }


    /**
     * Check for unsupported strcutures in config
     *
     * @param $model
     * @return bool
     */

    public function checkUnsupportedConfig($model)
    {
//        if (!empty($model['scopes']))
//        {
//            foreach ($model['scopes'] as $list_alias=>$list_arr)
//            {
//                if (isset($list_arr['form']) || isset($list_arr['tabs']) )
//                {
//                    return false;
//                }
//                foreach ($list_arr as $list_col)
//                {
//                    if (isset($list_col['format']))
//                    {
//                        return false;
//                    }
//                }
//            }
//        }

        return true;
    }


    /**
     * Check if config directory is writable
     *
     * @return bool
     */

    public  function checkConfigDir()
    {

        return (is_dir($this->config_dir_path) && is_writable($this->config_dir_path));

    }//

    /**
     * Check if models directory is writable
     * @return bool
     */

    public  function checkModelsDir()
    {

        return (is_dir($this->model_dir_path) && is_writable($this->model_dir_path));

    }//

    /**
     * @return bool Check if migrations directory is writable
     */

    public  function checkMigrationsDir()
    {

        return (is_dir(base_path() . '/database/migrations') && is_writable(base_path() . '/database/migrations'));

    }//

    /**
     * Return db tables
     * @return array
     */
    function getTables($for_index = false)
    {

        $this->app['db']->setFetchMode(\PDO :: FETCH_ASSOC);
        $tables = $this->app['db']->select('SELECT  table_name FROM   information_schema.tables WHERE   table_type = \'BASE TABLE\' AND   table_schema=?', [env('DB_DATABASE')]);
        $arr = [];

        foreach ($tables as $table)
        {

            if (strpos($table['table_name'],'crud_') !==0 && strpos($table['table_name'],'crud_file') === false)
            {
                if ($for_index && in_array($table['table_name'], $this->skip_tables))   {
                        continue;
                }
                $arr[] = $table['table_name'];
            }

        }

        $return = [];
        foreach ($arr as $k=> $table_name)
        {
            if ($this->getModelConfig($table_name))
            {
                $return[] = $table_name;
                unset($arr[$k]);
            }
        }

        $this->app['db']->setFetchMode($this->app['config']->get('database.fetch'));

        return array_merge($return, $arr);

    }

    /**
     * Return columns for a specific table
     * @param $table
     * @return mixed
     */
    function getTableColumns($table)
    {
        if (!isset($this->table_columns[$table]))
        {
            $cols = $this->app['db']->connection()->getSchemaBuilder()->getColumnListing($table);
            sort($cols);
            $this->table_columns[$table] = $cols;
        }
        return $this->table_columns[$table];
    }

    /**
     * Return INT columns for a specific table
     * @param $table
     * @return mixed
     */
    function getIntTableColumns($table)
    {
        if (!$this->table_int_columns) {

            $this->table_int_columns = [];
            $col_types = $this->getTableColumnTypes($table);

            foreach ($col_types as $col_name => $col_type) {

                $col_type = strtolower($col_type);

                if (strpos($col_type, 'int') !== false) {
                    $this->table_int_columns[] = $col_name;
                }
            }
        }

        return $this->table_int_columns;
    }

    /**
     * Return table column types in  column=>data_type format
     * @param $table
     * @return mixed
     */
    function getTableColumnTypes($table)
    {
        if (!isset($this->table_column_types[$table]))
        {
            $this->app['db']->setFetchMode(\PDO :: FETCH_ASSOC);

            $cols =  $this->app['db']->select('SELECT  COLUMN_NAME, DATA_TYPE FROM   information_schema.COLUMNS WHERE   TABLE_SCHEMA = ? AND TABLE_NAME=?', [env('DB_DATABASE'),$table]);
            foreach ($cols as $col)
            {
                $this->table_column_types[$table][$col['COLUMN_NAME']] = $col['DATA_TYPE'];
            }
            $this->app['db']->setFetchMode($this->app['config']->get('database.fetch'));
        }
        return $this->table_column_types[$table];
    }

    /**
     * Get crud models already defined
     * @return array
     */
    function getAvailableModels()
    {
        if (!$this->available_models)
        {

            $configs = $this->getCrudConfigs();
            if ($configs)
            {
                $this->available_models = array_keys($configs);
            }
        }

        return $this->available_models;
    }

    /**
     * Get crud-model config
     * @param $table_name Table name
     * @param $force If use cached values or force recreate
     * @return mixed
     */
    function getModelConfig($table_name, $force=false)
    {

        if (!isset($this->model_configs[$table_name]) || $force)
        {
            if (file_exists(config_path('crud/'.$table_name.'.php')))
            {
                $this->model_configs[$table_name] = $this->app['config']->get('crud.'.$table_name);
                $this->model_configs[$table_name]['filters'] = [];
                if (!empty($this->model_configs[$table_name]['list']))
                {
                    foreach ($this->model_configs[$table_name]['list'] as $alias => $list)
                    {
                        if (!empty($list['filter']))
                        {
                            $this->model_configs[$table_name]['filters'][$alias] = $list['filter'];
                        }
                    }
                }
            } else {
                $this->model_configs[$table_name] = false;
            }
        }

        return $this->model_configs[$table_name];

    }


    /**
     * Detect if any crud-models are already configured
     * @return bool|null
     */
    function modelsDefined()
    {
        if ($this->is_models_defined === null)
        {
            $configs = $this->getCrudConfigs();
            if (count($configs)>1)
            {
                $this->is_models_defined = true;
            } else {
                $this->is_models_defined = false;
            }
        }

        return $this->is_models_defined;
    }

    /**
     * Get all crud configs
     *
     * @return array
     */
    private function getCrudConfigs()
    {
        if (!$this->crud_configs)
        {
            $this->crud_configs = [];
            $files =  \File::files(config_path('crud'));
            foreach ($files as $file)
            {
                $cfg = include ($file);
                $table = str_replace(['crud_', '.php'],'', basename($file));
                $cfg['table'] = $table;
                $this->crud_configs[$cfg['name']] = $cfg;
            }
        }

        return $this->crud_configs;
    }

    /**
     * Get an array of available form field types
     *
     * @return array
     */
    function getAvailableFieldTypes()
    {
        $types = [];
        foreach (Form:: getAvailControls() as $control)
        {
            if ($control instanceof WizardableField)
            {
                if (!$control->wizardIsForRelationOnly())
                {
                    $types[$control->controlType()] = $control;
                }
            }


        }
        return $types;
    }

    /**
     * Get an array of available filter field types
     * @return array
     */
    function getAvailableFilterTypes()
    {
        $types = [];
        foreach (Form :: getAvailControls() as $control)
        {
            if ($control instanceof WizardableField)
            {
                if ($control instanceof FormControlFilterable)
                {
                    $types[$control->controlType()] = $control->wizardCaption();
                }
            }
        }
        return $types;
    }


    /**
     * Get an array of available form field types for  relations
     *
     * @return array
     */
    function getAvailableRelationFieldTypes($multiple=false)
    {
        $types = [];

        foreach (Form :: getAvailControls() as $control)
        {
            if ($control instanceof WizardableField)
            {
                if ($control->wizardIsForRelation())
                {
                    if ($multiple)
                    {
                         if ($control->wizardIsForManyRelation()) {
                             $types[$control->controlType()] = $control->wizardCaption();
                         }
                    } else {
                        $types[$control->controlType()] = $control->wizardCaption();
                    }

                }
            }


        }
        return $types;

        return $ret;
    }


    /**
     * Get an array of all columns for all crud-models
     *
     * @param $forModel
     * @return array
     */
    function getAllModelColumns($forModel=null)
    {
        $ret = [];
        $configs = $this->getCrudConfigs();
        foreach ($configs as $model=>$cfg)
        {
            $ret[snake_case($model)] = $this->getTableColumns($cfg['table']);
            if ($forModel && snake_case($model) == snake_case($forModel))
            {
                return $ret[snake_case($model)];
            }
        }
        return $ret;

    }//

    /**
     * @return array List of available date formats in php and js forms
     */
    function getAvailableDateFormats()
    {

        return [
            ['js'=>'dd.mm.yyyy', 'php' => 'd.m.Y' ],
            ['js'=>'dd/mm/yyyy', 'php' => 'd/m/Y' ],
            ['js'=>'dd-mm-yyyy', 'php' => 'd-m-Y' ],
            ['js'=>'mm/dd/yyyy', 'php' => 'm/d/Y' ],
            ['js'=>'mm/dd/yy', 'php' => 'm/d/y' ],
            ['js'=>'yyyy-mm-dd', 'php' => 'Y-m-d' ],


        ];

    }//

    /**
     * @return array List of available datetime formats in php and js forms
     */
    function getAvailableDateTimeFormats()
    {

        return [
            ['js'=>'DD.MM.YYYY', 'php' => 'd.m.Y' ],
            ['js'=>'MM/DD/YYYY', 'php' => 'm/d/Y' ],
            ['js'=>'YYYY-MM-DD', 'php' => 'Y-m-d' ],
            ['js'=>'DD.MM.YYYY HH:mm', 'php' => 'd.m.Y H:i' ],
            ['js'=>'MM/DD/YYYY hh:mm A', 'php' => 'm/d/Y h:i A' ],
            ['js'=>'YYYY-MM-DD HH:mm', 'php' => 'Y-m-d H:i' ],


        ];

    }//

    /**
     * @return array List of available locales
     */
    function getAvailableLocales()
    {

        return [
                'en',
                'ru'

            ];

    }//

    /**
     * @return array List of available wysiwyg
     */
    function getAvailableEditors()
    {
        return  [
            '' => 'None',
            'summernote' => 'Summernote',
//            'tinymce' => 'TinyMCE',
            'mde' => 'Markdown',
        ];
    }

    /**
     * Return the list of defined relations for the table name
     * @param $table
     * @return array
     */
    function  getModelRelations($table)
    {
        $config = $this->getModelConfig($table);
        $ret = [];
        if (!empty($config['fields']) && is_array($config['fields']))
        {
            foreach ($config['fields'] as $k=>$field)
            {
                if (!empty($field['relation']))
                {
                    $field['relation_name'] = $k;
                    $ret[$k] = $field;
                }
            }
        }

        return $ret;

    }//

    function getAllPivotTables()
    {
        $tables = [];
        $configs = $this->getCrudConfigs();
        foreach ($configs as $conf)
        {

            $rels = $this->getModelRelations($conf['table']);
            foreach ($rels as $relation)
            {
                if (!empty($relation['pivot_table']))
                {
                    $tables[] = $relation['pivot_table'];
                }
            }

        }

        return $tables;
    }//

    public function getPossiblePivotTables()
    {
        $all = $this->getTables();
        $pivot = $this->getAllPivotTables();
        $ret = [];
        foreach ($all as $table)
        {
            if (strpos($table,'_') !== false && !in_array($table, $pivot))
            {
                $ret[] = $table;
            }
        }

        return $ret;

    }

    function getAllLists()
    {
        $ret = [];
        $configs = $this->getCrudConfigs();
        foreach ($configs as $model=>$cfg)
        {
            if (!empty($cfg['list']))
            {
                $ret[$model] = $cfg['list'];
            }
        }
        return $ret;
    }

    /**
     * Define if field type is date/date-time
     *
     * @param $type string Field type
     */
    public  function isDateField($type)
    {
        return in_array($type, [Field::DATE_RANGE, Field::DATE, Field::DATE_TIME]);
    }

    /**
     * Return value if the list `data` property is a model attribute not a field name
     *
     * @param $table
     * @param $data_alias
     * @return string|null
     */
    public function resolveColDataProp($table, $data_alias)
    {
        $conf = $this->getModelConfig($table);
        if ($conf && !empty($conf['fields']))
        {
            if (!key_exists($data_alias,$conf['fields']) && !strpos($data_alias,'::'))
            {
                return $data_alias;
            }
        }
    }

    /**
     * Return value if the list `data` property is a relation
     *
     * @param $table
     * @param $data_alias
     * @return array|null
     */
    public function resolveColDataRelation($data_alias)
    {
        if (strpos($data_alias,'::') !== false)
        {
            return explode('::', $data_alias);
        }
    }


    /**
     * Get available formatters for model by it's table name
     *
     * @param $table
     * @return array
     */
    public function getAvailableFormatters($table)
    {
        $formatters = [];
        $config = $this->getModelConfig($table);
        if (!empty($config) && isset($config['name']))
        {
            $obj = CrudModel::createInstance($config['name']);
            $formatters = $obj->getAvailFormatters();
        }

        return $formatters;
    }//

    /**
     * Get available slect options providers for model
     *
     * @param $table
     * @return array
     */
    public function getAvailableSelectOptionsProviders($model)
    {
        $obj = CrudModel::createInstance($model);
        return $obj->getAvailOptionGenerators();

    }//


    /**
     * Get control instance by type alias
     *
     * @param $type
     * @return null|\Skvn\Crud\Contracts\FormControl
     */
    public function getControlByType($type)
    {
        return Form::getControlByType($type);
    }

    /**
     * Get select options for on_delete select
     *
     * @return array
     */
    public function getOndeleteActions()
    {
        return [
            ''=>'No action',
            'delete'=>'Cascade delete',
            'set_null'=>'Set null',
            
        ];
    }


    /**
     * Get available options for audit trail
     * 
     * @return array
     */
    public function getTrackHistoryOptions()
    {

        return [
            ''=>'Do not track',
            'detail'=>'Detailed',
            'summary'=>'Summary',

        ];

    }

    /**
     * Get available options for tree list
     *
     * @return array
     */
    public function getAvailableTreeLists()
    {

        return [
            'jstree'=>'JSTree',
            'dt_tree'=>'DataTables tree grid',
            'dt_flat'=>'Datatables flat grid with breadcrumbs',

        ];

    }

}
