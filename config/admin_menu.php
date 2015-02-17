<?php

return [


    [
        'title'  => 'Пользователи',
        'acl' => 'user',
        'icon_class' => 'fa fa-users',
        'kids' => [


            [
                'route' => ['name'=>'crud_index', 'args'=>['model'=>'user']],
                'title'=>'Пользователи',
                'acl'=>'user'
            ],
        ]
    ],




];
