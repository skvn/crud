<?php

Route::group(array('namespace' => 'LaravelCrud\Controller','middleware' => 'auth'), function() {
    Route::get('admin/crud/tree/{model}',                               array('as' => 'crud_tree',                  'uses' => 'CrudController@tree'));
    Route::post('admin/crud/{model}/move_tree',                         array('as' => 'crud_move_tree',             'uses' => 'CrudController@treeMove'));
    Route::get('admin/crud/{model}',                                    array('as' => 'crud_index',                 'uses' => 'CrudController@index'));
    Route::get('admin/crud/{model}/edit/{id}',                          array('as' => 'crud_edit',                  'uses' => 'CrudController@edit'));
    Route::post('admin/crud/{model}/update/{id}',                       array('as' => 'crud_update',                'uses' => 'CrudController@update'));
    Route::post('admin/crud/{model}/filter/{context}',                  array('as' => 'crud_filter',                'uses' => 'CrudController@filter'));
    Route::get('admin/crud/{model}/list/{list}',                        array('as' => 'crud_list',                  'uses' => 'CrudController@clist'));
    Route::post('admin/crud/{model}/delete',                            array('as' => 'crud_delete',                'uses' => 'CrudController@delete'));
    Route::post('admin/crud/{model}/{id}/command/{command_name}',       array('as' => 'crud_command',               'uses' => 'CrudController@command'));
    Route::any('some/url',                                              array('as' => 'crud_table_columns',          'uses' => 'CrudController@crudTableColumns'));
});

