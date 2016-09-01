<?php
$adm_route_params = [
    'prefix'=>'admin',
    'namespace' => 'Skvn\Crud\Controllers',
    'middleware' =>  explode(',',env('APP_BACKEND_MIDDLEWARE',"web,auth"))
];

$domain = env('APP_BACKEND_DOMAIN');
if ($domain)
{
    $adm_route_params['domain'] = $domain;
}
Route::group($adm_route_params, function() {

    Route::get('/',                                                 ['as' => 'crud_welcome',               'uses' => 'CrudController@welcome']);

    Route::get('util/tooltip/fetch',                                [                                      'uses' => 'CrudController@crudTooltipFetch']);
    Route::post('util/tooltip/update',                              [                                      'uses' => 'CrudController@crudTooltipUpdate']);
    //handle simple uploads
    Route::post('crud/attach_upload',                               ['as' => 'upload_attach',              'uses' => 'CrudAttachController@upload']);
    Route::any('util/crud/table_columns',                           ['as' => 'crud_table_columns',         'uses' => 'CrudController@crudTableColumns']);

});

$adm_route_params['middleware'][] = 'Skvn\Crud\Middleware\ModelAcl';

Route::group($adm_route_params, function() {
    Route::get('crud/{model}/tree/{scope}',                         ['as' => 'crud_tree',                  'uses' => 'CrudController@crudTree']);
    Route::get('crud/{model}/autocomplete',                         ['as' => 'crud_autocomplete',          'uses' => 'CrudController@crudAutocompleteList']);
    Route::get('crud/{model}/select_options',                       ['as' => 'crud_select_options',        'uses' => 'CrudController@crudAutocompleteSelectOptions']);
    Route::get('crud/{model}/tree_options',                         ['as' => 'crud_tree_options',          'uses' => 'CrudController@crudTreeOptions']);
    Route::post('crud/{model}/move_tree',                           ['as' => 'crud_move_tree',             'uses' => 'CrudController@crudTreeMove']);
    Route::get('crud/{model}',                                      ['as' => 'crud_index',                 'uses' => 'CrudController@crudIndex']);
    Route::get('crud/{model}/{scope}',                              ['as' => 'crud_scoped_index',          'uses' => 'CrudController@crudIndex']);
    Route::get('crud/{model}/edit/{id}',                            ['as' => 'crud_edit',                  'uses' => 'CrudController@crudEdit']);
    Route::post('crud/{model}/update/{id}',                         ['as' => 'crud_update',                'uses' => 'CrudController@crudUpdate']);
    Route::post('crud/{model}/filter/{scope}',                      ['as' => 'crud_filter',                'uses' => 'CrudController@crudFilter']);
    Route::get('crud/{model}/list/{scope}',                         ['as' => 'crud_list',                  'uses' => 'CrudController@crudList']);
    Route::get('crud/{model}/popup_index/{scope}',                  ['as' => 'crud_popup_index',           'uses' => 'CrudController@crudPopupIndex']);
    Route::get('crud/{model}/excel/{scope}',                        ['as' => 'crud_list_excel',            'uses' => 'CrudController@crudListExcel']);
    Route::post('crud/{model}/delete',                              ['as' => 'crud_delete',                'uses' => 'CrudController@crudDelete']);
    Route::post('crud/{model}/{id}/command/{command_name}',         ['as' => 'crud_command',               'uses' => 'CrudController@crudCommand']);
    Route::post('crud/validate',                                    ['as' => 'crud_validate',               'uses' => 'CrudController@crudRunValidators']);
});

Route::group(array('namespace' => 'Skvn\Crud\Controllers'), function() {
    Route::get('attach/{model}/{id}/{filename}',                    ['as' => 'download_attach',            'uses' => 'CrudAttachController@download']);
    Route::post('typo/check',                                       [                                      'uses' => 'CrudController@typoCheck']);
    Route::post('typo/check2',                                      [                                      'uses' => 'CrudController@typoCheck2']);
});




