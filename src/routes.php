<?php

Route::group(array('prefix'=>'admin','namespace' => 'Skvn\Crud\Controllers','middleware' => ['web','auth']), function() {

    Route::get('/',                                                 array('as' => 'crud_welcome',               'uses' => 'CrudController@welcome'));
    Route::get('crud/{model}/tree/{scope}',                       array('as' => 'crud_tree',                  'uses' => 'CrudController@crudTree'));
    Route::post('crud/{model}/move_tree',                         array('as' => 'crud_move_tree',             'uses' => 'CrudController@crudTreeMove'));
    Route::get('crud/{model}',                                    array('as' => 'crud_index',                 'uses' => 'CrudController@crudIndex'));
    Route::get('crud/{model}/edit/{id}',                          array('as' => 'crud_edit',                  'uses' => 'CrudController@crudEdit'));
    Route::post('crud/{model}/update/{id}',                       array('as' => 'crud_update',                'uses' => 'CrudController@crudUpdate'));
    Route::post('crud/{model}/filter/{scope}',                    array('as' => 'crud_filter',                'uses' => 'CrudController@crudFilter'));
    Route::get('crud/{model}/list/{scope}',                       array('as' => 'crud_list',                  'uses' => 'CrudController@crudList'));
    Route::post('crud/{model}/delete',                            array('as' => 'crud_delete',                'uses' => 'CrudController@crudDelete'));
    Route::post('crud/{model}/{id}/command/{command_name}',       array('as' => 'crud_command',               'uses' => 'CrudController@crudCommand'));
    Route::any('util/crud/table_columns',                               array('as' => 'crud_table_columns',          'uses' => 'CrudController@crudTableColumns'));

//    Route::get('util/tooltip/fetch',                                    array(                                       'uses' => 'CrudController@crudTooltipFetch'));
//    Route::get('util/notify/fetch',                                     array(                                       'uses' => 'CrudController@crudNotifyFetch'));
//    Route::post('util/tooltip/update',                                  array(                                       'uses' => 'CrudController@crudTooltipUpdate'));




    Route::any('crud_setup/table_cols/{table}',   array('uses' => 'WizardController@getTableColumns'));
    Route::any('crud_setup/menu',   array('as' => 'wizard_menu', 'uses' => 'WizardController@menu'));
    Route::any('crud_setup/{table}',   array('as' => 'wizard_model', 'uses' => 'WizardController@model'));
    Route::any('crud_setup',   array('as' => 'wizard_index', 'uses' => 'WizardController@index'));

    //handle simple uploads
    Route::post('crud/attach_upload', array('as' => 'upload_attach', 'uses' => 'CrudAttachController@upload'));




});

Route::group(array('namespace' => 'Skvn\Crud\Controllers'), function() {
    Route::get('attach/{id}/{filename}', array('as' => 'download_attach', 'uses' => 'CrudAttachController@download'));
});




