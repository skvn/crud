<?php namespace Skvn\Crud\Wizard;



use Skvn\Crud\Exceptions\WizardException;
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
    protected $wizard;
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
    private $column_types = [];

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
        $this->config_path = config_path('crud').'/crud_'.$this->table.'.php';
        @mkdir(dirname($this->config_path));
        if (file_exists($this->config_path))
        {
            $old_config = include $this->config_path;
            $this->config_data = array_merge($old_config,$this->config_data);
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

            if (!empty($rel['local_key']))
            {
                $key = $rel['local_key'];

            } else {
                $key = $rel_arr['relation_name'];
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
                if (!empty($f['type']) && ($f['type'] == Form::FIELD_FILE || $f['type'] == Form::FIELD_MULTI_FILE))
                {
                    $f['name'] = $k;
                    $f['multi'] = ($f['type'] == Form::FIELD_MULTI_FILE?1:0);
                    $this->addFileField($f);
                }
            }
        }
//        if (!empty($this->config_data['single_files']))
//        {
//
//            foreach ($this->config_data['single_files'] as $fname =>$fdata)
//            {
//                if (!empty($fdata['use'])) {
//
//                }
//
//            }
//        }
//
//        if (!empty($this->config_data['multi_files']))
//        {
//
//
//            foreach ($this->config_data['multi_files'] as $fdata)
//            {
//                if ($fdata && is_array($fdata)) {
//                    $this->addFileField(array_merge(['multi' => 1], $fdata));
//                }
//            }
//        }
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
        $this->config_data['fields'][$data['name']] = array_merge(['editable'=>1,'type' => ($data['multi']?Form::FIELD_MULTI_FILE: Form::FIELD_FILE)], $data);
    }

    /**
     * Process fields data
     */
    private  function processFields()
    {

        $this->config_data['form_fields'] = [];

        if (!empty($this->config_data['fields']))
        {
                        
            foreach ($this->config_data['fields'] as $k=> $f)
            {

                if (!isset($this->column_types[$k]) && empty($f['relation']))
                {
                    $this->add_fields[$k] = $f; 
                }
                
                if (!empty($f['type']))
                {
                    $this->config_data['fields'][$k]['editable'] = 1;
                    $this->config_data['form_fields'][] = $k;
                }
                //process date
                if (!empty($f['type']) && $f['type'] == Form::FIELD_DATE)
                {
                    $formats = $this->wizard->getAvailableDateFormats();
                    $this->config_data['fields'][$k]['format'] = $formats[$f['format']]['php'];
                    $this->config_data['fields'][$k]['jsformat'] = $formats[$f['format']]['js'];
                    $this->config_data['fields'][$k]['db_type'] = $this->column_types[$k];

                }

                //process date time
                if (!empty($f['type']) && $f['type'] == Form::FIELD_DATE_TIME)
                {
                    $formats = $this->wizard->getAvailableDateTimeFormats();
                    $this->config_data['fields'][$k]['format'] = $formats[$f['format']]['php'];
                    $this->config_data['fields'][$k]['jsformat'] = $formats[$f['format']]['js'];
                    $this->config_data['fields'][$k]['db_type'] = $this->column_types[$k];

                }

                //if any textarea has a html editor, add inline img trait
                if (!empty($f['type']) && $f['type'] == Form::FIELD_TEXTAREA && !empty($f['editor']))
                {
                    if (!isset($this->config_data['inline_img']))
                    {
                        $this->config_data['inline_img'] = [];
                        if (!isset($this->config_data['traits']))
                        {
                            $this->config_data['traits'] = [];
                        }
                        $this->config_data['traits'][] = 'InlineImgTrait';
                    }

                    $this->config_data['inline_img'][] = $k;

                }


            }




        }
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
                        //$key = $list_alias . '_filter_' . $k;

                        $field = $f;
                        $field['column'] = $k;

                        //process date
                        if (!empty($f['type']) && $f['type'] == Form::FIELD_DATE_RANGE) {
                            $formats = $this->wizard->getAvailableDateFormats();
                            $field['format'] = $formats[$f['format']]['php'];
                            $field['jsformat'] = $formats[$f['format']]['js'];
                            $field['db_type'] = $this->column_types[$k];

                        }

                        $filter_fields[$k] = $field;
                    }

                }
                //$this->config_data['list'][$list_alias]['filter'] = var_export($filter_fields, 1);
                //$this->config_data['list'][$list_alias]['filter'] = $filter_fields;

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

                $searchable = 0;
                //columns
                if (!empty($list['columns']))
                {
                    $cols = [];
                    foreach ($list['columns'] as $k=>$column)
                    {

                        if (!empty($column['data_col']))
                        {
                            $column['data'] = $column['data_col'];
                            unset($column['data_col']);

                        } else if (!empty($column['data_rel']))
                        {
                            $column['data'] = $column['data_rel'].'::'.$column['data_rel_attr'];
                            unset($column['data_rel']);
                            unset($column['data_rel_attr']);
                        }

                        if (empty($column['data'] ))
                        {
                            continue;
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
                    $this->config_data['list'][$alias]['columns'] = $cols;
                }

                //actions
                if (!empty($list['list_actions'])) {
                    $actions = [];
                    foreach ($list['list_actions'] as $action) {

                        if (is_array($action)&& !empty($action['command']))
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


            }

            //exit;
        }


    }//

    /**
     * Prepare config data for recording
     */
    private function prepareConfigData()
    {

        //form

        if (!empty($this->config_data['fields']))
        {
            $form_fields = [];
            foreach ($this->config_data['fields'] as $key=>$f)
            {

                if (isset($f['editable']) && $f['editable'] )
                {
                    $form_fields[] = $key;
                }

            }

            if (empty($this->config_data['form_tabs'])) {

                $this->config_data['form'] = $form_fields;

            } else {

                $this->config_data['form'] = [];
                $form_tabs = json_decode($this->config_data['form_tabs'], true);
                unset($this->config_data['form_tabs']);
                $tabs = [];
                foreach ($form_tabs as $i=>$tab)
                {
                    if (!empty($tab['alias']))
                    {
                        $alias = $tab['alias'];
                    } else {
                        $alias = 'tab_'.$i;
                    }
                    $tabs[$alias] = ['title'=>$tab['title']];
                    $this->config_data['form'][$alias] = $tab['fields'];
                }
                $this->config_data['tabs'] = $tabs;
            }
        }

        //track timestamps?
        if (isset($this->column_types['created_at']) && isset($this->column_types['updated_at']))
        {
            if ($this->column_types['created_at'] == 'int' && $this->column_types['updated_at']=='int')
            {
                $this->config_data['timestamps'] = 'int';
            } else {
                $this->config_data['timestamps'] = $this->column_types['created_at'];
            }
        }

        //track author?
        if (isset($this->column_types['created_by']) && isset($this->column_types['updated_by']))
        {

             $this->config_data['track_author'] = 1;

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

        //var_dump($this->config_data);
        //exit;
        //$val = $this->app['view']->make('crud_wizard::crud_config', ['model'=>$this->config_data])->render();
        //var_dump($val);
        //eval("\$arr = $val");
        //file_put_contents($this->config_path,"<?php \n return ".var_export($arr, 1).";");
        //file_put_contents($this->config_path,"<?php \n return ".$val.";");
        //var_dump($this->config_data);
        $conf = json_encode($this->buildConfig(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
        $conf = str_replace(['{', '}'], ['[', ']'], $conf);
        $conf = str_replace('":', '" =>', $conf);
        //var_dump($conf);
        //exit;
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
            $conf['tree'] = 1;
            $conf['tree_level_column'] = "tree_level";
            $conf['tree_path_column'] = "tree_path";
            $conf['tree_pid_column'] = "tree_pid";
            $conf['tree_order_column'] = "tree_order";
        }
        if (!empty($this->config_data['acl']))
        {
            $conf['acl'] = $this->config_data['acl'];
        }
        if (!empty($this->config_data['ent_name'])) {
            $conf['ent_name'] = $this->config_data['ent_name'];
        }

        if (!empty($this->config_data['ent_name_r']))
        {
            $conf['ent_name_r'] = "user";
        }
        if (!empty($this->config_data['ent_name_v']))
        {
            $conf['ent_name_v'] = "user";
        }
        if (!empty($this->config_data['dialog_width']))
        {
            $conf['dialog_width'] = 1000;
        }
        if (!empty($this->config_data['timestamps']))
        {
            $conf['timestamps'] = true;
            $conf['timestamps_type'] = $this->config_data['timestamps'];
        }
        if (!empty($this->config_data['track_authors']))
        {
            $conf['authors'] = true;
        }


        if (!empty($this->config_data['list']))
        {
            $conf['list'] = $this->config_data['list'];
        }
//            $conf['list'] = [];
//            foreach ($this->config_data['list'] as $alias => $ldata)
//            {
//                
//                $list = [];
//                $list['title'] = $ldata['title'];
//                $list['description'] = $ldata['description'];
//                if (!empty($ldata['multi_select'])) $list['multiselect'] = true;
//                $list['columns'] = [];
//                foreach ($ldata['columns'] as $col)
//                {
//                    if (!empty($col['data']))
//                    {
//                        $column = [
//                            'data' => $col['data'],
//                            'title' => $col['title'],
//                            'orderable' => !empty($col['orderable'])
//                        ];
//                        if (!empty($col['hint'])) $column['hint'] = ['default' => $col['hint']];
//                        if (!empty($col['orderable']) && !empty($col['default_order']))
//                        {
//                            $column['default_order'] = $col['default_order'];
//                        }
//                        if (!empty($col['searchable'])) $column['searchable'] = 1;
//                        if (!empty($col['invisible'])) $column['invisible'] = 1;
//                        if (!empty($col['width'])) $column['width'] = $col['width'];
//                        $list['columns'][] = $column;
//                    }
//                }
//                $list['filter'] = $ldata['filter'];
//                if (!empty($ldata['actions']))
//                {
//                    $list['list_actions'] = $ldata['actions'];
//                }
//                if (!empty($ldata['use_tabs']))
//                {
//                    $list['edit_tab'] = 1;
//                }
//                if (!empty($ldata['use_tabbed_form']))
//                {
//                    $list['form_tabbed'] = 1;
//                }
//                $list['buttons'] = [];
//
//                if (!empty($ldata['edit_btn'])) $list['buttons']['single_edit'] = true;
//                if (!empty($ldata['delete_btn'])) $list['buttons']['single_delete'] = true;
//                if (!empty($ldata['mass_delete_btn'])) $list['buttons']['mass_delete'] = true;
//                if (!empty($ldata['customize_cols'])) $list['buttons']['customize_columns'] = true;
//
//                $conf['list'][$alias] = $list;
//            }
//        }

        if (!empty($this->config_data['form'])) {
            $conf['form'] = $this->config_data['form'];
        }

        if (!empty($this->config_data['tabs'])) {
            $conf['tabs'] = $this->config_data['tabs'];
            $conf['form_tabbed'] = 1;
        }



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
                    $dbtype = $migrator->getColumDbTypeByEditType($fdesc['type']);
                    if (!empty($dbtype)) {
                        $columns[$fname] = $dbtype;
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