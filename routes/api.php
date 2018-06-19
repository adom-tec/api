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
    Route::resource('patients', 'PatientController');
    Route::get('documenttypes', 'DocumentTypeController@index');
    Route::get('unittimes', 'UnitTimeController@index');
    Route::get('genders', 'GenderController@index');
    Route::get('patienttypes', 'PatientTypeController@index');
    Route::resource('professionals', 'ProfessionalController');
    Route::resource('coordinators', 'CoordinatorController');
    Route::resource('entities', 'EntityController');
    Route::resource('entities/{entity}/plans', 'PlanEntityController');
    Route::resource('services', 'ServiceController');
    Route::resource('plans/{plan}/services', 'PlanServiceController');
    Route::get('specialties', 'SpecialtyController@index');
    Route::get('accountTypes', 'AccountTypeController@index');
    Route::resource('patients/{patient}/services', 'PatientServiceController');
    Route::get('copaymentfrecuencies', 'CoPaymentFrecuencyController@index');
    Route::get('servicefrecuencies', 'ServiceFrecuencyController@index');
});
