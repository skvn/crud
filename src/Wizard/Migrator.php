<?php namespace Skvn\Crud\Wizard;

use Illuminate\Http\Request;
use Skvn\Crud\Form\Form;

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
    }

    public  function createTable($table=null)
    {
        if (!$table) {
            $table = $this->request->get('table_name');
        }
        if (empty($table))
        {
            throw new CrudWizardException('No table name specified');
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
            'class'  =>   "Alter".studly_case($table)."Table".ucfirst(strtolower(str_random(10))),
            'columns' => $cols
        ];

        $path = base_path() . '/database/migrations/' . date('Y_m_d_His') .
            '_'.snake_case($migration['class']).'.php';

        file_put_contents($path,
            $this->app['view']->make('crud_wizard::migrations/alter_table', compact('migration'))->render()
        );
    }

    public  function getColumDbTypeByEditType($type)
    {
        if (!empty($this->type_map[$type])) {
            return $this->type_map[$type];
        }
    }//
    
    public  function migrate()
    {

        if (empty($this->error) && $this->isMigrateAllowed()) {
            return \Artisan::call("migrate", ['--force' => true, '--quiet' => true]);
        }

        return false;
    }//

    private function isMigrateAllowed()
    {
        $this->error = "Running automatic migrations is prohibited by your configuration file. Please, run php artisan migrate from the command line";
        return false;
    }



}
