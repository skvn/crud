<?php
return [
    'acls' => [
        'all' => "Полный доступ",
        'some_ability' => "Какое-то разпешение",
        'tooltips' => "Управление тултипами"
    ],
    'roles' => [
        'root' => [
            'title' => "Root",
            'acls' => [
                'all' => '*'
            ]
        ],
        'role' => [
            'title' => "Роль",
            'acls' => [
                'x1' => "*",
                'x2' => "[crud]",
            ]
        ],
    ]
];
