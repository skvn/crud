<?php

Route::group(array('namespace' => 'Admin',/*'middleware' => 'auth'*/), function() {
    Route::get('admin',                                                 array('as' => 'crud_welcome',               'uses' => 'AdminController@welcome'));
    Route::get('admin/crud/{model}/tree/{scope}',                       array('as' => 'crud_tree',                  'uses' => 'AdminController@crudTree'));
    Route::post('admin/crud/{model}/move_tree',                         array('as' => 'crud_move_tree',             'uses' => 'AdminController@crudTreeMove'));
    Route::get('admin/crud/{model}',                                    array('as' => 'crud_index',                 'uses' => 'AdminController@crudIndex'));
    Route::get('admin/crud/{model}/edit/{id}',                          array('as' => 'crud_edit',                  'uses' => 'AdminController@crudEdit'));
    Route::post('admin/crud/{model}/update/{id}',                       array('as' => 'crud_update',                'uses' => 'AdminController@crudUpdate'));
    Route::post('admin/crud/{model}/filter/{scope}',                    array('as' => 'crud_filter',                'uses' => 'AdminController@crudFilter'));
    Route::get('admin/crud/{model}/list/{scope}',                       array('as' => 'crud_list',                  'uses' => 'AdminController@crudList'));
    Route::post('admin/crud/{model}/delete',                            array('as' => 'crud_delete',                'uses' => 'AdminController@crudDelete'));
    Route::post('admin/crud/{model}/{id}/command/{command_name}',       array('as' => 'crud_command',               'uses' => 'AdminController@crudCommand'));
    Route::get('util/tooltip/fetch',                                    array(                                       'uses' => 'AdminController@crudTooltipFetch'));
    Route::get('util/notify/fetch',                                     array(                                       'uses' => 'AdminController@crudNotifyFetch'));
    Route::post('util/tooltip/update',                                  array(                                       'uses' => 'AdminController@crudTooltipUpdate'));
    Route::any('util/crud/table_columns',                               array('as' => 'crud_table_columns',          'uses' => 'AdminController@crudTableColumns'));
});

Route::get('attach/{id}/{filename}', array('as' => 'download_attach', 'uses' => 'AttachController@download'));