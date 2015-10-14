<?php

Route::group(array('namespace' => '\LaravelAttach\Controller'), function() {
    Route::get('download/{id}', array('as' => 'download_attach', 'uses' => 'AttachController@download'));
});
