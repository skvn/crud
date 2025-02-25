<?php

return [
    'model_namespace'                     => '\App\Models',
    'history_trait'                       => '\Skvn\Crud\Traits\ModelHistoryTrait',
    'tree_trait'                          => '\Skvn\Crud\Traits\ModelTreeTrait',
    'collection_class'                    => \Skvn\Crud\Models\CrudCollection :: class,
    'app_title'                           => env('APPLICATION_TITLE', 'SkVn/CRUD application'),
    'auto_migrate_allowed'                => env('AUTO_MIGRATE_EMABLED', true),
    'app_logo'                            => env('APPLICATION_LOGO', null),
    'middleware'                          => env('APP_BACKEND_MIDDLEWARE', 'web,auth'),
    'domain'                              => env('APP_BACKEND_DOMAIN'),
    'replace_morph_classes_with_basename' => false,
    'assets'                              => [
        'js' => [
         '/js/app.js',
        ],
        'css' => [

        ],
        'builtjs' => null,
        'ymaps_key' => null,
    ],
    'form_controls' => [
        Skvn\Crud\Form\Checkbox :: class,
        Skvn\Crud\Form\Date :: class,
        Skvn\Crud\Form\DateRange :: class,
        Skvn\Crud\Form\DateTime :: class,
        Skvn\Crud\Form\File :: class,
        Skvn\Crud\Form\Image :: class,
        Skvn\Crud\Form\MultiFile :: class,
        Skvn\Crud\Form\Number :: class,
        Skvn\Crud\Form\Range :: class,
        Skvn\Crud\Form\Select :: class,
        Skvn\Crud\Form\Tags :: class,
        Skvn\Crud\Form\Textarea :: class,
        Skvn\Crud\Form\Text :: class,
        Skvn\Crud\Form\Tree :: class,
        Skvn\Crud\Form\Radio :: class,
        Skvn\Crud\Form\EntitySelect :: class,
        Skvn\Crud\Form\LinkedModelsTable :: class,
    ],

    'relations' => [
        'hasOne'        => Skvn\Crud\Models\RelationHasOne :: class,
        'hasFile'       => Skvn\Crud\Models\RelationHasFile :: class,
        'hasMany'       => Skvn\Crud\Models\RelationHasMany :: class,
        'hasManyFiles'  => Skvn\Crud\Models\RelationHasManyFiles :: class,
        'belongsTo'     => Skvn\Crud\Models\RelationBelongsTo :: class,
        'belongsToMany' => Skvn\Crud\Models\RelationBelongsToMany :: class,
        'morphTo'       => Skvn\Crud\Models\RelationMorphTo :: class,
        'morphMany'     => Skvn\Crud\Models\RelationMorphMany :: class,
    ],
];
