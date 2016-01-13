<?php

Route::group(array('namespace' => 'Skvn\Crud\Controllers',/*'middleware' => 'auth'*/), function() {

    Route::get('admin',                                                 array('as' => 'crud_welcome',               'uses' => 'CrudController@welcome'));
    Route::get('admin/crud/{model}/tree/{scope}',                       array('as' => 'crud_tree',                  'uses' => 'CrudController@crudTree'));
    Route::post('admin/crud/{model}/move_tree',                         array('as' => 'crud_move_tree',             'uses' => 'CrudController@crudTreeMove'));
    Route::get('admin/crud/{model}',                                    array('as' => 'crud_index',                 'uses' => 'CrudController@crudIndex'));
    Route::get('admin/crud/{model}/edit/{id}',                          array('as' => 'crud_edit',                  'uses' => 'CrudController@crudEdit'));
    Route::post('admin/crud/{model}/update/{id}',                       array('as' => 'crud_update',                'uses' => 'CrudController@crudUpdate'));
    Route::post('admin/crud/{model}/filter/{scope}',                    array('as' => 'crud_filter',                'uses' => 'CrudController@crudFilter'));
    Route::get('admin/crud/{model}/list/{scope}',                       array('as' => 'crud_list',                  'uses' => 'CrudController@crudList'));
    Route::post('admin/crud/{model}/delete',                            array('as' => 'crud_delete',                'uses' => 'CrudController@crudDelete'));
    Route::post('admin/crud/{model}/{id}/command/{command_name}',       array('as' => 'crud_command',               'uses' => 'CrudController@crudCommand'));
    Route::get('util/tooltip/fetch',                                    array(                                       'uses' => 'CrudController@crudTooltipFetch'));
    Route::get('util/notify/fetch',                                     array(                                       'uses' => 'CrudController@crudNotifyFetch'));
    Route::post('util/tooltip/update',                                  array(                                       'uses' => 'CrudController@crudTooltipUpdate'));
    Route::any('util/crud/table_columns',                               array('as' => 'crud_table_columns',          'uses' => 'CrudController@crudTableColumns'));



    Route::get('admin/crud_setup',   array('as' => 'wizard_index', 'uses' => 'WizardController@index'));
    Route::get('admin/crud_setup/{table}',   array('as' => 'wizard_model', 'uses' => 'WizardController@model'));
    Route::post('admin/crud_setup/create_models',   array('as' => 'wizard_create_models', 'uses' => 'WizardController@createModels'));

});

Route::group(array('namespace' => 'Skvn\Attach\Controllers'), function() {
    Route::get('attach/{id}/{filename}', array('as' => 'download_attach', 'uses' => 'AttachController@download'));
});




