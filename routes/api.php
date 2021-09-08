<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    
    //protected routes go within this route
    Route::post('/logout', [AuthController::class, 'logout']);
    
});
*/

Route::resource('address','App\Http\Controllers\AddressController');
Route::resource('patient','App\Http\Controllers\PatientController');

Route::resource('patientcontact','App\Http\Controllers\PatientContactController');
Route::resource('patientcontactdata','App\Http\Controllers\PatientContactDataController');
Route::resource('lkupcontactdatatype','App\Http\Controllers\LkupContactDataTypeController');
Route::resource('lkupcontacttype','App\Http\Controllers\LkupContactTypeController');

Route::resource('sureveystepone','App\Http\Controllers\SurveyStepOneController');
Route::resource('sureveysteptwo','App\Http\Controllers\SurveyStepTwoController');
Route::resource('sureveystepthree','App\Http\Controllers\SurveyStepThreeController');
//Route::resource('surveycomplete','App\Http\Controllers\SurveyCompleteController');

Route::put('/surveycomplete','App\Http\Controllers\SurveyCompleteController@update');

Route::put('/patientprofile','App\Http\Controllers\PatientProfileController@update');

Route::resource('patientfull','App\Http\Controllers\PatientFullController');
Route::resource('patientdiagnosis','App\Http\Controllers\PatientDiagnosisController');
Route::resource('patientdoctor','App\Http\Controllers\PatientDoctorController');




// routes to lookup table, which have no creates or updates (for select form fields)
Route::resource('lkuppatientethnicity','App\Http\Controllers\LkupPatientEthnicityController');
Route::resource('lkuppatientdiagnosistreatment','App\Http\Controllers\LkupPatientDiagnosisTreatmentController');
Route::resource('lkuppatientdiagnosisadditional','App\Http\Controllers\LkupPatientDiagnosisAdditionalController');
Route::resource('lkuppatientdiagnosisremotesite','App\Http\Controllers\LkupPatientDiagnosisRemoteSiteController');
Route::resource('lkuppatientdiagnosiscancerstage','App\Http\Controllers\LkupPaitentDiagnosisCancerStageController');
Route::resource('lkuppatientdiagnosiscancertype','App\Http\Controllers\LkupPaitentDiagnosisCancerTypeController');
Route::resource('lkuppatientdiagnosiscelltype','App\Http\Controllers\LkupPaitentDiagnosisCellTypeController');
Route::resource('lkuppatientdiagnosisscore','App\Http\Controllers\LkupPaitentDiagnosisPerformanceScoreController');
Route::resource('lkuppatientdiagnosistumorsite','App\Http\Controllers\LkupPaitentDiagnosisTumorSiteController');
Route::resource('lkuppatientdiagnosistumorsize','App\Http\Controllers\LkupPaitentDiagnosisTumorSizeController');

// search routes for auto complete fields
Route::get('/lkuppatientdiagnosisadditional/search/{label}',['App\Http\Controllers\LkupPatientDiagnosisAdditionalController', 'search']);
Route::get('/lkuppatientdiagnosistreatment/search/{label}',['App\Http\Controllers\LkupPatientDiagnosisTreatmentController', 'search']);
Route::get('/lkuppatientdiagnosisremotesite/search/{label}',['App\Http\Controllers\LkupPatientDiagnosisRemoteSiteController', 'search']);


/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
