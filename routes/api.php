<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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
Route::resource('address','App\Http\Controllers\AddressController');

Route::resource('patient','App\Http\Controllers\PatientController');

Route::resource('patientcontact','App\Http\Controllers\PatientContactController');

Route::resource('lkupcontacttype','App\Http\Controllers\LkupContactTypeController');

Route::resource('patientdiagnosistreatment','App\Http\Controllers\PatientDiagnosisTreatmentController');

Route::resource('patientdiagnosisadditional','App\Http\Controllers\PatientDiagnosisAdditionalController');

Route::resource('patientdiagnosisremotesite','App\Http\Controllers\PatientDiagnosisRemoteSiteController');

Route::resource('patientcontactdata','App\Http\Controllers\PatientContactDataController');

Route::resource('lkupcontactdatatype','App\Http\Controllers\LkupContactDataTypeController');



Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
