<?php namespace Skvn\Crud\Wizard;

use Illuminate\Http\Request;
use Skvn\Crud\Form\Form;
use Skvn\Crud\Exceptions\WizardException;

class Migrator
{

    public $error;

    private $request, $app;

    private $type_map = [
        Form::FIELD_CHECKBOX => 'tinyInteger',
        Form::FIELD_DATE => 'date',
        Form::FIELD_DATE_TIME => 'dateTime',
        Form::FIELD_NUMBER => 'double',
        //Form::FIELD_DECIMAL => 'double',
        Form::FIELD_SELECT => 'integer',
        Form::FIELD_TEXT => 'string',
        Form::FIELD_TEXTAREA => 'longText'

    ];
    


    public function __construct(Request $request=null)
    {
        $this->request = $request;
        $this->app = app();
        $this->app['view']->addNamespace('crud_wizard', __DIR__ . '/../../stubs');
    }

    public  function createTable($table=null)
    {
        if (!$table) {
            $table = $this->request->get('table_name');
        }
        if (empty($table))
        {
            throw new WizardException('No table name specified');
        }

        

        $existing = (new Wizard())->getTables();

        if (in_array($table,$existing))
        {
            $this->error = 'Table '.$table.' already exists';

            
        } else {
            
            $migration = [
                'table_name' => $table,
                'class'  =>   "Create".studly_case($table)."Table"
            ];
            
            $path = base_path() . '/database/migrations/' . date('Y_m_d_His') .
                '_create_' . $table . '_table.php';

            file_put_contents($path,
                $this->app['view']->make('crud_wizard::migrations/create_table', compact('migration'))->render()
            );

        }

        return $this;

    }//

    
    public  function createPivotTable($data)
    {
        $data['class']  =   "Create".studly_case($data['table_name'])."PivotTable";
        $path = base_path() . '/database/migrations/' . date('Y_m_d_His') .
            '_create_' . $data['table_name'] . '_pivot_table.php';

        file_put_contents($path,
            $this->app['view']->make('crud_wizard::migrations/pivot', ['pivot'=>$data])->render()
        );
    }

    public  function  appendColumns($table, $cols)
    {
        $migration = [
            'table_name' => $table,
            'class'  =>   "Alter".studly_case($table)."Table".md5($table.implode(',',$cols)),
            'columns' => $cols
        ];

        if ($this->checkMigrationName($migration['class'])) {


            $path = base_path() . '/database/migrations/' . date('Y_m_d_His') .
                '_' . snake_case($migration['class']) . '.php';

         return  file_put_contents($path,
                $this->app['view']->make('crud_wizard::migrations/alter_table', compact('migration'))->render()
            );
        }

        return false;
    }

    public  function getColumDbTypeByEditType($type)
    {
        if (!empty($this->type_map[$type])) {
            return $this->type_map[$type];
        }
    }//

    private  function checkMigrationName($class_name)
    {
        $file_name = snake_case($class_name);
        $all_migrations = \File::allFiles(base_path() . '/database/migrations');
        foreach ($all_migrations as $file)
        {
           if (strpos($file->getBasename(), $file_name) !== false)
           {
               return false;
           }

        }
        return true;
    }

    public  function migrate()
    {

        if (empty($this->error) && $this->isMigrateAllowed()) {
            return \Artisan::call("migrate", ['--force' => true, '--quiet' => true]);
        }

        return false;
    }//

    private function isMigrateAllowed()
    {
        if ($this->app['config']['crud_common.auto_migrate_allowed'])
        {
            return true;

        } else {
            $this->error = "Running automatic migrations is prohibited by your configuration file. <br>Please, <b>run php artisan migrate</b> from the command line";
            return false;
        }
    }



}
