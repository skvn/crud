<?php


return [

    /* if is tree */
    'tree' => 1,
    'tree_level_column' => 'tree_depth',
    'tree_path_column' => 'tree_path',

    /* Access level rule name (optional)*/
    'acl' => 'user',
    'ent_name' => 'user',
    /* Russian Родительный падеж (optional) */
    'ent_name_r' => 'user',
    /* Russian Винительный падеж (optional) */
    'ent_name_v' => 'user',
    /* Breadcrumbs */
    'bc' => [['href'=>'/admin/users','title'=>'Управление пользователями']],
    /* Modal dialog width (optional) */
    'dialog_width' => 1000,
    /* Breadcrumbs */
    'bc' => [['href'=>'/admin/projects','title'=>'Управление проектами']],

    /* track timestamp*/
    'timestamps' => true,

    /* track author*/
    'authors' => true,

    /* Datatables list description  In case of the only list for the model*/

    'list'=> [
        'default' => [
            'title'=>'Список пользователей',
            /* MASS SELECT checkboxes */
            'multiselect'=>true,

            'columns'=>[
                [ "data"=> "id", "orderable"=>true, 'hint' => ['index' => 'tooltip index', 'default' => 'tooltip default text'], 'default_order'=>'desc'],
                [ "data"=> "email", "orderable"=>true, 'title'=>'Email', 'invisible' => 1],
            ],
            'filters' => [],

            /* 1 for using tabs  instead of popups */
            'edit_tab'=>1,

            /* 1 for using tabbed (complex) form */
            'form_tabbed'=>1,

            'buttons' =>
                [
                    /* show edit button*/
                    'single_edit' => true,
                    /* show delete button*/
                    'single_delete'=> true,
                    /* show mass delete  button*/
                    'mass_delete' => true,
                    'customize_columns' => true
                ],

        ]
    ],

    /* Fields names to include in edit form UI */

    'form' => ['email', /*'first_name', 'last_name',*/ /*'role_id',*//*'acl_role'*/],

    'fields'=>[

        //tree parent
        'tree_pid' =>
            [
                'type' => \LaravelCrud\CrudConfig::FIELD_SELECT,
                'title' => 'Родительская страница',
                'model' => 'page', //underscores
                'required' =>1,
                'relation' => \LaravelCrud\CrudConfig::RELATION_BELONGS_TO,
                'relation_name' => 'parent',
                //FOR TRACKABLE models
                'track' =>1,


            ],

        'email' =>
            [
                'type'=>'text',
                'required'=>1,
                'title' => 'Email',
                'hint' => 'ref id of hint (tooltip system)',
                'hint_default' => 'default hit countent',
                'disable_autocomplete' => false
            ],


        //date field
        'date' =>
            [
                'type'=>\LaravelCrud\CrudConfig::FIELD_DATE,
                'required'=>1,
                'format' => 'd.m.Y',
                'jsformat' => 'dd.mm.yyyy',
                'title' => 'Дата'
            ],

        //checkbox
        'active' =>
            [
                'type'=>\LaravelCrud\CrudConfig::FIELD_CHECKBOX,
                'title' => 'Активна'
            ],

        //number field
        'sfc_num_links' =>
            [
                'type'=>\LaravelCrud\CrudConfig::FIELD_NUMBER,
                'required'=>0,
                'title' => 'caption',
                'hint' => '',
                'hint_default' => '',
                'min' =>0, //optional
                'max' =>10, //optional
                'step'=>1, //optional
                'tab' => "tab_sfc"
            ],

//        'first_name' =>
//            [
//                'type'=>'text',
//                'required'=>1,
//                'title' => 'Имя'
//            ],
//
//        'last_name' =>
//            [
//                'type'=>'text',
//                'required'=>1,
//                'title' => 'Фамилия'
//            ],
//        'acl_role' =>
//            [
//                'type' => \LaravelCrud\CrudConfig::FIELD_SELECT,
//                'required' => 1,
//                'method_options' => "getAclRoleOptions",
//                'title' => "Роль"
//            ],



    ],
];