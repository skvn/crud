<?php namespace Skvn\Crud\Wizard;



use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Exceptions\WizardException;
use Skvn\Crud\Form\Field;
use Skvn\Crud\Form\Form;

/**
 * Class CrudModelPrototype
 * @package Skvn\Crud
 * @author Vitaly Nikolenko <vit@webstandart.ru>
 */
class CrudModelPrototype
{


    /**
     * @var array
     */
    protected $config_data;

    /**
     * @var array
     */
    protected $old_config_data;

    /**
     * @var
     */
    protected $app;
    /**
     * @var
     */
    protected $namespace;
    /**
     * @var
     */
    protected $path;
    /**
     * @var string
     */
    protected $config_path;
    /**
     * @var Wizard
     */
    public $wizard;
    /**
     * @var
     */
    protected $table;

    /**
     * @var array  data for migrations
     */
    protected $migrations_data = [];

    /**
     * @var bool Indicator if any migations were created during model recording
     */
    public $migrations_created = false;

    /**
     * @var array Table column types arrays
     */
    public $column_types = [];

    /**
     * @var array Array of columns that should be added to the table
     */
    private $add_fields = [];

    /**
     * @var string Contains error if any
     */
    public $error;

    /**
     * CrudModelPrototype constructor.
     * @param $config_data
     */
    public function __construct($config_data)
    {

        if (empty($config_data['table']))
        {
            throw new WizardException('Table  for model prototype is not defined');
        }


        $this->config_data = $config_data;
        $this->table = $this->config_data['table'];
        $this->wizard = new Wizard();
        $this->column_types = $this->wizard->getTableColumnTypes($this->table);
        $this->app = app();
        $this->namespace = ltrim($this->app['config']['crud_common.model_namespace'],'\\');
        $this->config_data['namespace'] = $this->namespace;
        $folderExpl = explode('\\',$this->namespace);
        $folder = $folderExpl[(count($folderExpl)-1)];
        $this->path = app_path($folder);
        $this->config_path = config_path('crud').'/'.$this->table.'.php';
        @mkdir(dirname($this->config_path));
        if (file_exists($this->config_path))
        {
            $this->old_config_data = include $this->config_path;
            $this->config_data = array_merge($this->old_config_data,$this->config_data);
            //var_dump($this->config_data);
        }

        $this->processRelations();
        $this->processFiles();
        $this->processFields();
        $this->processLists();
        $this->processFilters();
        $this->prepareConfigData();

    }//

    /**
     * Make fields out of relations data
     */
    private function processRelations()
    {

        if (!empty($this->config_data['relations']))
        foreach ($this->config_data['relations'] as $rel)
        {

            $rel_arr = [
                'relation' => $rel['type'],
                'title' => $rel['title'],
                'model' => $rel['model'],
                'relation_name' => trim($rel['name']),

            ];

            //need to record pivot?
            if ($rel['type'] == \Skvn\Crud\Models\CrudModel::RELATION_BELONGS_TO_MANY && $rel['pivot'] == '0')
            {

                $pdata = [];

                $tables = [
                    snake_case($this->config_data['name']),
                    snake_case($rel['model'])
                ];

                sort($tables);
                $pdata['table_name'] = implode('_', $tables);
                $pdata['self_key'] = snake_case($this->config_data['name']).'_id';
                $pdata['foreign_key'] = snake_case($rel['model']).'_id';
                $this->migrations_data['pivot'][] = $pdata;
                $rel_arr['pivot_table'] = $pdata['table_name'];
                $rel_arr['pivot_self_key'] = $pdata['self_key'];
                $rel_arr['pivot_foreign_key'] = $pdata['foreign_key'];

            } else if ($rel['type'] == \Skvn\Crud\Models\CrudModel::RELATION_BELONGS_TO_MANY && $rel['pivot'] == '1')
            {
                $rel_arr['pivot_table'] = $rel['pivot_table'];
                $rel_arr['pivot_self_key'] = $rel['pivot_self_key'];
                $rel_arr['pivot_foreign_key'] = $rel['pivot_foreign_key'];
            }

            if (!empty($rel['ref_column']))
            {
                $rel_arr['ref_column'] = $rel['ref_column'];
            }

            if (!empty($rel['editable']))
            {
                $rel_arr['editable'] = 1;
                if (!empty($rel['find']))
                {
                    $rel_arr['find'] = $rel['find'];
                }
                $rel_arr['type'] = $rel['form_field'];
                if (!empty($rel['required']))
                {
                    $rel_arr['required'] = 1;
                }

            }

            if (!empty($rel['local_key'])) {

                $rel_arr['field'] = $rel['local_key'];
            }

            if (!empty($rel['on_delete'])) {

                $rel_arr['on_delete'] = $rel['on_delete'];
            }

            $key = $rel_arr['relation_name'];
            unset($rel_arr['relation_name']);

            if (!empty($rel['sort']))
            {
                $sort = json_decode($rel['sort'], true);
                //\Log :: info($sort, ['browsify' => 1]);

                if (is_array($sort) && count($sort))
                {
                    $rel_arr['sort'] = $sort;
                }

            }
            $this->config_data['fields'][$key] = $rel_arr;

        }


    }//

    /**
     * Process editable files
     */
    private  function processFiles()
    {


        if (!empty($this->config_data['fields'])) {

            foreach ($this->config_data['fields'] as $k => $f) {
                if (!empty($f['type']) && in_array($f['type'], [Field::FILE, Field::MULTI_FILE, Field::IMAGE]))
                {
                    $f['name'] = $f['rel_name'];
                    $f['field'] = $k;
                    $f['multi'] = ($f['type'] == Field::MULTI_FILE?1:0);
                    $this->config_data['fields'][$f['rel_name']] = $f;
                    unset($this->config_data['fields'][$k]);
                    $this->addFileField($f);
                }
            }
        }

    }

    /**
     * Add one file field to config data
     * @param $data
     */
    private function addFileField($data)
    {
        if (!isset($this->config_data['traits']))
        {
            $this->config_data['traits'] = [];
        }
        if (!in_array('ModelAttachmentTrait', $this->config_data['traits']))
        {
            $this->config_data['attaches'] = [];
            $this->config_data['traits'][] = 'ModelAttachmentTrait';
        }

        if ($data['multi'])
        {
            $tables = [
                snake_case($this->config_data['name']),
                'crud_file'
            ];

            sort($tables);
            $pivot_table  = implode('_', $tables);

            $this->migrations_data['pivot'][] =
                [
                    'table_name' => $pivot_table,
                    'self_key' => snake_case($this->config_data['name']).'_id',
                    'foreign_key' => 'crud_file_id'
                ];


        } else {
            $pivot_table = '';
        }
        $this->config_data['attaches'][] = ['column'=>$data['name'], 'multi'=>$data['multi'], 'pivot_table'=>$pivot_table];
        $this->config_data['fields'][$data['name']] = array_merge(['editable'=>1,'type' => ($data['multi']?Field::MULTI_FILE: Field::FILE)], $data);
    }

    /**
     * Process fields data
     */
    private  function processFields()
    {


        $this->config_data['form_fields'] = [];
        $fields_to_delete = [];

        $fields = [];
        if (!empty($this->config_data['fields']))
        {

            foreach ($this->config_data['fields'] as $key=> $f)
            {

                $k = $key;
                if (empty($f['relation'])) {
                    if (empty($f['fields']) && empty($f['field'])) {
                        if (!isset($this->column_types[$k])) {
                            $this->add_fields[$k] = $f;
                        }
                    } elseif (! empty($f['field']))
                    {
                        if (!isset($this->column_types[$f['field']])) {
                            $this->add_fields[$f['field']] = $f;
                        }
                    }
                    elseif (! empty($f['fields']))
                    {
                        if (!isset($this->column_types[$f['fields'][0]])) {
                            $this->add_fields[$f['fields'][0]] = $f;
                        }
                        if (!isset($this->column_types[$f['fields'][1]])) {
                            $this->add_fields[$f['fields'][1]] = $f;
                        }
                    }

                }

                if (!empty($f['type']))
                {
                    $this->config_data['fields'][$k]['editable'] = 1;
                    $this->config_data['form_fields'][] = $k;

                    if (!empty($f['rel_name']))
                    {
                        unset($f['rel_name']);
                    }

                    //process field config by field
                    if ($control = Form::getControlByType($f['type']))
                    {
                        if ($control instanceof WizardableField) {
                            $control->wizardCallbackFieldConfig($k, $f, $this);
                            $control->wizardCallbackModelConfig($k, $f, $this);
                        }
                    }

                }



                if (!empty($this->old_config_data['fields'][$k]))
                {
                    $fields[$k] = array_merge($this->old_config_data['fields'][$k],$f);
                } else {
                    $fields[$k] = $f;
                }



            }


        }

        $this->config_data['fields'] = $fields;
    }//

    /**
     * Process filters data
     */
    private  function processFilters()
    {

        if (!empty($this->config_data['filters']))
        {
            foreach ($this->config_data['filters'] as $list_alias=> $fields)
            {

                $filter_fields = [];
                foreach ($fields as $k=>$f) {
                    if (!empty($f['type'])) {
                       
                        $field = $f;
                        $field['column'] = $k;

                        //process date
                        if (!empty($f['type']) && $f['type'] == Field::DATE_RANGE) {
                            $formats = $this->wizard->getAvailableDateFormats();
                            $field['format'] = $formats[$f['format']]['php'];
                            $field['jsformat'] = $formats[$f['format']]['js'];
                            $field['db_type'] = $this->column_types[$k];

                        }

                        $filter_fields[$k] = $field;
                    }

                }
                
            }

        }
    }//


    /**
     * Prepare lists config
     *
     */
    private function processLists()
    {

        if (!empty($this->config_data['list']))
        {
            foreach ($this->config_data['list'] as $alias=>$list)
            {

                //sort
                if (!empty($list['sort']))
                {
                    $sort = [];
                    foreach ($list['sort'] as $row)
                    {
                        if (!empty($row['column'])) {
                            $sort[$row['column']] = $row['order'];
                        }
                    }

                    $this->config_data['list'][$alias]['sort'] = $sort;
                }
                //form
                if (empty($list['form_tabs']) && !empty($list['form'])) {

                    $this->config_data['list'][$alias]['form'] = explode(",",$list['form']);

                } else if (!empty($list['form_tabs'])) {


                    $form_tabs = json_decode($list['form_tabs'], true);
                    //var_dump($form_tabs);
                    unset( $this->config_data['list'][$alias]['form_tabs']);
                    $tabs = [];
                    foreach ($form_tabs as $i=>$tab)
                    {
                        if (!empty($tab['alias']))
                        {
                            $tab_alias = $tab['alias'];
                        } else {
                            $tab_alias = 'tab_'.$i;
                        }

                        $oldTab = $this->old_config_data['scopes'][$alias]['form'][$tab_alias]??[];
                        unset($oldTab['fields']);
                        $tabs[$tab_alias] = array_merge(['title'=>$tab['title']],$oldTab);
                        $tabs[$tab_alias]['fields'] = $tab['fields']??[];

                    }

                    $this->config_data['list'][$alias]['form'] = $tabs;
                    //$this->config_data['list'][$alias]['tabs'] = $tabs;
                   // $this->config_data['list'][$alias]['form_tabbed'] = 1;

                }

                $searchable = 0;
                //columns
                if (!empty($list['columns']))
                {
                    $cols = [];
                    foreach ($list['columns'] as $k=>$column)
                    {

                        if (!is_numeric($k))
                        {
                            continue;
                        }
                        if (!empty($column['data_col']))
                        {
                            $column['data'] = $column['data_col'];


                        } else if (!empty($column['data_rel']))
                        {
                            $column['data'] = $column['data_rel'].'::'.$column['data_rel_attr'];
                        }

                        if (empty($column['data'] ))
                        {
                            continue;
                        }

                        if (isset($column['data_rel'])) {
                            unset($column['data_rel']);
                        }
                        if (isset($column['data_rel_attr'])) {
                            unset($column['data_rel_attr']);
                        }
                        if (isset($column['data_col'])) {
                            unset($column['data_col']);
                        }
                        if (!empty($column['hint']))
                        {
                            $column['hint'] = ['default' => $column['hint']];

                        }
                        if (!empty($column['searchable']))
                        {
                            $searchable = 1;
                        }

                        $cols[] = $column;
                    }
                    $this->config_data['list'][$alias]['searchable'] = $searchable;
                    unset($this->config_data['list'][$alias]['columns']);
                    $this->config_data['list'][$alias]['list'] = $cols;
                }

                //actions
                if (!empty($list['list_actions'])) {
                    $actions = [];
                    foreach ($list['list_actions'] as $k=> $action) {

                        if (!is_numeric($k))
                        {
                            unset ($this->config_data['list'][$alias]['list_actions'][$k]);
                        }
                        if (is_array($action)&&
                            (!empty($action['command']) || !empty($action['event'])  || !empty($action['popup'])))
                        {
                            $actions[] = $action;
                        }
                    }

                    if (count($actions)) {
                        $this->config_data['list'][$alias]['list_actions'] = $actions;
                    } else {
                        unset($this->config_data['list'][$alias]['list_actions']);
                    }
                }

                if (empty($this->config_data['is_tree']) && isset($this->config_data['list'][$alias]['list_type']))
                {
                    unset($this->config_data['list'][$alias]['list_type']);
                }

            }

        }


    }//

    /**
     * Prepare config data for recording
     */
    private function prepareConfigData()
    {


        if (!empty($this->config_data['track_history'])) {
            $this->config_data['traits'][] = $this->app['config']['crud_common.history_trait'];
        }

        if (!empty($this->config_data['is_tree'])) {
            $this->config_data['traits'][] = $this->app['config']['crud_common.tree_trait'];
        }


    }//

    /**
     * Record config and model files
     */
    public  function record()
    {

        $this->app['view']->addNamespace('crud_wizard', __DIR__ . '/../../stubs');

        $this->recordConfig();
        $this->recordModels();
        $this->recordMigrations();
        $this->migrate();


    }

    /**
     * Record configuration file
     */
    protected  function recordConfig()
    {

        $conf = json_encode($this->buildConfig(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
        $conf = str_replace(['{', '}'], ['[', ']'], $conf);
        $conf = str_replace('":', '" =>', $conf);
        file_put_contents($this->config_path,"<?php \n return ".$conf.";");

    }//

    protected function buildConfig()
    {
        $conf = [];
        $conf['name'] = $this->config_data['name'];
        if (!empty($this->config_data['title_field'])) {
            $conf['title_field'] = $this->config_data['title_field'];
        }
        if (!empty($this->config_data['is_tree']))
        {
            if (empty($this->old_config_data['tree'])) {
                $conf['tree'] =
                    [
                        'depth_column' => "tree_level",
                        'path_column' => "tree_path",
                        'pid_column' => "tree_pid",
                        'order_column' => "tree_order"
                    ];
            } else {
                $conf['tree'] = $this->old_config_data['tree'];
            }
        }
        if (!empty($this->config_data['acl']))
        {
            $conf['acl'] = $this->config_data['acl'];
        }
        if (!empty($this->config_data['ent_name'])) {
            $conf['ent_name'] = $this->config_data['ent_name'];
        }
        if (!empty($this->config_data['ent_name_r'])) {
            $conf['ent_name_r'] = $this->config_data['ent_name_r'];
        }

        if (!empty($this->config_data['ent_name_v'])) {
            $conf['ent_name_v'] = $this->config_data['ent_name_v'];
        }
        
        if (!empty($this->config_data['dialog_width']))
        {
            $conf['dialog_width'] = 1000;
        }
//        if (!empty($this->config_data['timestamps']))
//        {
//            $conf['timestamps'] = true;
//            $conf['timestamps_type'] = $this->config_data['timestamps'];
//        }
        if (!empty($this->config_data['track_history']))
        {
            $conf['track_history'] = $this->config_data['track_history'];
        }


        if (!empty($this->config_data['list']))
        {
            $conf['scopes'] = $this->config_data['list'];
        }

        if (!empty($this->config_data['form'])) {
            $conf['form'] = $this->config_data['form'];
        }

//        if (!empty($this->config_data['tabs'])) {
//            $conf['tabs'] = $this->config_data['tabs'];
//            $conf['form_tabbed'] = 1;
//        }



        $conf['fields'] = [];

        if (!empty($this->config_data['fields'])) {
            foreach ($this->config_data['fields'] as $index => $fdata) {
                if (!empty($fdata['type']) || !empty($fdata['relation'])) {
                    $field = [];
                    foreach ($fdata as $k => $v) {
                        $field[$k] = $v;
                    }
                    $conf['fields'][$index] = $field;
                }
            }
        }
        return $conf;
    }

    /**
     * Record Model files
     */
    protected  function  recordModels()
    {
        //record main model (ONLY ONCE)
        if (!file_exists($this->path.'/'.$this->config_data['name'].'.php'))
        {
            @mkdir($this->path);
            file_put_contents($this->path.'/'.$this->config_data['name'].'.php',
                $this->app['view']->make('crud_wizard::crud_model_class', ['model'=>$this->config_data])->render()
            );
        }

        //record base model
        @mkdir($this->path.'/Crud');
        file_put_contents($this->path.'/Crud/'.$this->config_data['name'].'Base.php',
            $this->app['view']->make('crud_wizard::crud_base_model_class', ['model'=>$this->config_data])->render()
        );


    }//

    /**
     * Record migrations
     */
    protected function recordMigrations()
    {
        
        $this->recordPivotMigrations();
        $this->recordAddFieldsMigrations();


    }//

    /**
     *  Record mirations for add fields
     */
    private function recordAddFieldsMigrations()
    {
        if (count($this->add_fields))
        {

            $migrator = new Migrator();
            $columns = [];

            foreach ($this->add_fields as $fname=>$fdesc)
            {
                if (!empty($fdesc['type'])) {
                    if ($control = Form::getControlByType($fdesc['type'])) {
                        if ($control instanceof WizardableField) {
                            $dbtype = $migrator->getColumDbTypeByEditType($fdesc['type']);
                            if (!empty($dbtype)) {
                                $columns[$fname] = $dbtype;
                            }
                        }
                    }
                }
            }


            if (count($columns)) {
                if ($migrator->appendColumns($this->table, $columns)) {
                    $this->migrations_created = true;
                }
            }

        }
    }//

    
    /**
     *  run artisan migrate
     */
    private function migrate()
    {
        if ($this->migrations_created)
        {
            $migrator = new Migrator();
            if (!$migrator->migrate())
            {
                $this->error = $migrator->error;
            }
        }
    }
    
    
    private function recordPivotMigrations()
    {
        if (!empty($this->migrations_data['pivot']) && is_array($this->migrations_data['pivot']))
        {
            $migrator = new Migrator();
            foreach ($this->migrations_data['pivot'] as $p)
            {

                $this->migrations_created = true;
                $migrator->createPivotTable($p);

            }
        }

    }


}