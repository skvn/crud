<?php

Route::group(array('namespace' => 'Admin',/*'middleware' => 'auth'*/), function() {
    Route::get('admin/crud/tree/{model}',                               array('as' => 'crud_tree',                  'uses' => 'AdminController@tree'));
    Route::post('admin/crud/{model}/move_tree',                         array('as' => 'crud_move_tree',             'uses' => 'AdminController@treeMove'));
    Route::get('admin/crud/{model}',                                    array('as' => 'crud_index',                 'uses' => 'AdminController@index'));
    Route::get('admin/crud/{model}/edit/{id}',                          array('as' => 'crud_edit',                  'uses' => 'AdminController@edit'));
    Route::post('admin/crud/{model}/update/{id}',                       array('as' => 'crud_update',                'uses' => 'AdminController@update'));
    Route::post('admin/crud/{model}/filter/{context}',                  array('as' => 'crud_filter',                'uses' => 'AdminController@filter'));
    Route::get('admin/crud/{model}/list/{list}',                        array('as' => 'crud_list',                  'uses' => 'AdminController@clist'));
    Route::post('admin/crud/{model}/delete',                            array('as' => 'crud_delete',                'uses' => 'AdminController@delete'));
    Route::post('admin/crud/{model}/{id}/command/{command_name}',       array('as' => 'crud_command',               'uses' => 'AdminController@command'));
    Route::any('some/url',                                              array('as' => 'crud_table_columns',          'uses' => 'AdminController@crudTableColumns'));
});
