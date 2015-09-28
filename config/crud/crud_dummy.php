<?php


return [
    /* Table name */	
    'table'=>'dyno_news',	

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

    /* Datatables list description  In case of the only list for the model*/

    'list'=> [
        'title'=>'Список пользователей',
        'columns'=>[
            [ "data"=> "id", "orderable"=>true, 'hint' => ['index' => 'tooltip index', 'default' => 'tooltip default text']],
            [ "data"=> "email", "orderable"=>true, 'title'=>'Email'],

	/* MASS SELECT checkboxes */
	'multiselect'=>true		
	 /* MASS delete */
        'mass_delete' => true,

        ]


    ],

    /* Fields names to include in edit form UI */

    'form' => ['email', /*'first_name', 'last_name',*/ /*'role_id',*//*'acl_role'*/],

    'fields'=>[

        'email' =>
            [
                'type'=>'text',
                'required'=>1,
                'title' => 'Email',
                'hint' => 'ref id of hint (tooltip system)',
                'hint_default' => 'default hit countent',
                'disable_autocomplete' => false
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