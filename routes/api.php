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
Route::resource('patientcontactdata','App\Http\Controllers\PatientContactDataController');
Route::resource('patientdiagnosis','App\Http\Controllers\PatientDiagnosisController');

Route::resource('sureveystepone','App\Http\Controllers\SurveyStepOneController');
Route::resource('sureveysteptwo','App\Http\Controllers\SurveyStepTwoController');
Route::resource('sureveystepthree','App\Http\Controllers\SurveyStepThreeController');

Route::resource('patientfull','App\Http\Controllers\PatientFullController');

Route::resource('lkuppatientethnicity','App\Http\Controllers\LkupPatientEthnicityController');
Route::resource('lkupcontactdatatype','App\Http\Controllers\LkupContactDataTypeController');
Route::resource('lkupcontacttype','App\Http\Controllers\LkupContactTypeController');

Route::resource('lkuppatientdiagnosistreatment','App\Http\Controllers\LkupPatientDiagnosisTreatmentController');
Route::resource('lkuppatientdiagnosisadditional','App\Http\Controllers\LkupPatientDiagnosisAdditionalController');
Route::resource('lkuppatientdiagnosisremotesite','App\Http\Controllers\LkupPatientDiagnosisRemoteSiteController');
Route::resource('lkuppatientdiagnosiscancerstage','App\Http\Controllers\LkupPaitentDiagnosisCancerStageController');
Route::resource('lkuppatientdiagnosiscancertype','App\Http\Controllers\LkupPaitentDiagnosisCancerTypeController');
Route::resource('lkuppatientdiagnosiscelltype','App\Http\Controllers\LkupPaitentDiagnosisCellTypeController');
Route::resource('lkuppatientdiagnosisscore','App\Http\Controllers\LkupPaitentDiagnosisPerformanceScoreController');
Route::resource('lkuppatientdiagnosistumorsite','App\Http\Controllers\LkupPaitentDiagnosisTumorSiteController');
Route::resource('lkuppatientdiagnosistumorsize','App\Http\Controllers\LkupPaitentDiagnosisTumorSizeController');



Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
