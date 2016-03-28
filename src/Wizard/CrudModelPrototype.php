<?php namespace Skvn\Crud\Wizard;



use Skvn\Crud\CrudWizardException;
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
     * CrudModelPrototype constructor.
     * @param $config_data
     */
    public function __construct($config_data)
    {

        if (empty($config_data['table']))
        {
            throw new CrudWizardException('Table  for model prototype is not defined');
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
            if ($rel['type'] == \Skvn\Crud\CrudConfig::RELATION_BELONGS_TO_MANY && $rel['pivot'] == '0')
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

            } else if ($rel['type'] == \Skvn\Crud\CrudConfig::RELATION_BELONGS_TO_MANY && $rel['pivot'] == '1')
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


        if (!empty($this->config_data['single_files']))
        {

            foreach ($this->config_data['single_files'] as $fname =>$fdata)
            {
                if (!empty($fdata['use'])) {
                    $this->addFileField(array_merge(['name' => $fname, 'multi' => 0], $fdata));
                }

            }
        }

        if (!empty($this->config_data['multi_files']))
        {


            foreach ($this->config_data['multi_files'] as $fdata)
            {
                if ($fdata && is_array($fdata)) {
                    $this->addFileField(array_merge(['multi' => 1], $fdata));
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
        if (!in_array('AttachmentTrait', $this->config_data['traits']))
        {
            $this->config_data['attaches'] = [];
            $this->config_data['traits'][] = 'AttachmentTrait';
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

        if (!empty($this->config_data['fields']))
        {
                        
            foreach ($this->config_data['fields'] as $k=> $f)
            {
                if (!isset($this->column_types[$k]))
                {
                    $this->add_fields[$k] = $f; 
                }
                
                if (!empty($f['type']))
                {
                    $this->config_data['fields'][$k]['editable'] = 1;
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

                $filter_keys = [];
                foreach ($fields as $k=>$f) {
                    if (!empty($f['type'])) {
                        $key = $list_alias . '_filter_' . $k;
                        $filter_keys[] = $key;
                        $this->config_data['fields'][$key] = $f;
                        $this->config_data['fields'][$key]['column'] = $k;

                        //process date
                        if (!empty($f['type']) && $f['type'] == Form::FIELD_DATE_RANGE) {
                            $formats = $this->wizard->getAvailableDateFormats();
                            $this->config_data['fields'][$key]['format'] = $formats[$f['format']]['php'];
                            $this->config_data['fields'][$key]['jsformat'] = $formats[$f['format']]['js'];
                            $this->config_data['fields'][$key]['db_type'] = $this->column_types[$k];

                        }
                    }

                }
                $this->config_data['lists'][$list_alias]['filter'] = $filter_keys;

            }

        }
    }//






    /**
     * Prepare lists config
     *
     */
    private function processLists()
    {
        if (!empty($this->config_data['lists']))
        {
            foreach ($this->config_data['lists'] as $alias=>$list)
            {
                if (!empty($list['columns']))
                {
                    foreach ($list['columns'] as $k=>$column)
                    {
                        if (!empty($column['data_col']))
                        {
                            $this->config_data['lists'][$alias]['columns'][$k]['data'] = $column['data_col'];

                        } else if (!empty($column['data_rel']))
                        {
                            $this->config_data['lists'][$alias]['columns'][$k]['data'] = $column['data_rel'].'::'.$column['data_rel_attr'];
                        }
                    }
                }
            }
        }
    }//

    /**
     * Prepare config data for recording
     */
    private function prepareConfigData()
    {

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

            $this->config_data['form_fields'] = $form_fields;
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

        $this->app['view']->addNamespace('crud_wizard', __DIR__ . '/../stubs');

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

        $val = $this->app['view']->make('crud_wizard::crud_config', ['model'=>$this->config_data])->render();
        eval("\$arr = $val");
        file_put_contents($this->config_path,"<?php \n return ".var_export($arr, 1).";");

    }//

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
                $dbtype = $migrator->getColumDbTypeByEditType($fdesc['type']);
                if (!empty($dbtype)) {
                    $columns[$fname] = $dbtype;
                }
            }
            
            if (count($columns)) {
                $migrator->appendColumns($this->table, $columns);
                $this->migrations_created = true;
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
            $migrator->migrate();
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