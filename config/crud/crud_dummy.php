<?php


return [
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
    /* Don't show mass delete button (optional) */
    'no_mass_delete' => true,

    /* Datatables list description  In case of the only list for the model*/

    'list'=> [
        'title'=>'Список пользователей',
        'columns'=>[
            [ "data"=> "id","orderable"=>false,'title'=>'  ', 'width'=>30, 'ctype'=>'checkbox'],
            [ "data"=> "id", "orderable"=>true, 'hint' => 'tooltip index'],
            [ "data"=> "email", "orderable"=>true, 'title'=>'Email'],

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
                'hint' => 'ref id of hint (tooltip system)'
            ],


        //number field
        'sfc_num_links' =>
            [
                'type'=>\LaravelCrud\CrudConfig::FIELD_NUMBER,
                'required'=>0,
                'title' => 'Кол-во позиций на ссылку',
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