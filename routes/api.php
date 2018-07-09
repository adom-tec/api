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
    Route::get('professionals/copayment', 'PatientServiceController@getProfessionalsCopayment');
    Route::resource('professionals', 'ProfessionalController');
    Route::resource('coordinators', 'CoordinatorController');
    Route::resource('entities', 'EntityController');
    Route::resource('entities/{entity}/plans', 'PlanEntityController');
    Route::resource('services', 'ServiceController');
    Route::resource('plans/{plan}/services', 'PlanServiceController');
    Route::get('specialties', 'SpecialtyController@index');
    Route::get('accountTypes', 'AccountTypeController@index');
    Route::resource('patients/{patient}/services', 'PatientServiceController');
    Route::resource('copaymentfrecuencies', 'CoPaymentFrecuencyController');
    Route::resource('servicefrecuencies', 'ServiceFrecuencyController');
    Route::resource('services/{service}/observations', 'ServiceObservationController');
    Route::get('billedto', 'BilledToController@index');
    Route::resource('supplies', 'SupplyController');
    Route::resource('services/{service}/supplies', 'ServiceSupplyController');
    Route::get('finaldate', 'PatientServiceController@getFinalDate');
    Route::get('classifications', 'ClassificationController@index');
    Route::get('servicetypes', 'ServiceTypeController@index');
    Route::resource('notices', 'NoticeController');
    Route::get('lockservice', 'LockServiceController@index');
    Route::put('lockservice', 'LockServiceController@update');
    Route::get('states', 'StateAssignServiceController@index');
    Route::get('services/{service}/details/{me}', 'ServiceDetailController@index');
    Route::resource('services/{service}/details', 'ServiceDetailController');
    Route::get('me/services', 'PatientServiceController@getByProfessionalLogged');
    Route::get('cancelreasons', 'CancelReasonController@index');
    Route::get('answers', 'QualityAnswerController@index');
    Route::get('questions', 'QualityQuestionController@index');
    Route::put('services/{service}/details', 'ServiceDetailController@update');
    Route::post('services/{service}/answers', 'PatientServiceController@storeAnswer');
    Route::post('cancelreasons', 'DetailCancelReasonController@store');
    Route::get('copayments', 'CopaymentController@index');
    Route::put('copayments/{professional}', 'CopaymentController@update');
    Route::get('copayments/pdf/{id}', 'CopaymentController@pdf')->name('copayment.pdf');
    Route::get('copayments/excel/{id}', 'CopaymentController@pdf')->name('copayment.excel');
    Route::get('professionalrates', 'ProfesionalRateController@index');
    Route::get('getchartdata/{service}', 'PatientServiceController@getChartData');
    Route::get('irregularservices', 'PatientServiceController@getIrregularServices');
    Route::get('professionals/-1/services', 'PatientServiceController@getServicesWithoutProfessional');
    Route::get('reports/consolidado', 'ReportController@getConsolidadoReport');
});
