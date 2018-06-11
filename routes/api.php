<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'auth:api'], function () {
    Route::resource('users', 'UserController');
    Route::resource('roles', 'RoleController');
    Route::resource('users/{user}/roles', 'UserRoleController');
    Route::get('modules', 'ModuleController@index');
    Route::get('modules/{module}/actionsresources', 'ActionResourceController@getByModule');
    Route::get('roles/{role}/actionsresources', 'ActionResourceController@getByRole');
    Route::post('roles/{role}/actionsresources', 'ActionResourceController@store');
});
