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
// new pre screen routes for testing
Route::post('/prescreen','App\Http\Controllers\PrescreenController@index');
Route::put('/prescreen','App\Http\Controllers\PrescreenController@update');

// new pre screen tab routes for testing
Route::get('/prescreen_tab','App\Http\Controllers\PrescreenTabController@index');
Route::put('/prescreen_tab','App\Http\Controllers\PrescreenTabController@update');


Route::resource('address','App\Http\Controllers\AddressController');
Route::resource('patient','App\Http\Controllers\PatientController');
Route::resource('newsletter','App\Http\Controllers\NewsletterController');

Route::resource('patientcontact','App\Http\Controllers\PatientContactController');
Route::resource('patientcontactdata','App\Http\Controllers\PatientContactDataController');
Route::resource('lkupcontactdatatype','App\Http\Controllers\DropDowns\LkupContactDataTypeController');
Route::resource('lkupcontacttype','App\Http\Controllers\DropDowns\LkupContactTypeController');

Route::resource('sureveystepone','App\Http\Controllers\SurveyStepOneController');
Route::resource('sureveysteptwo','App\Http\Controllers\SurveyStepTwoController');
Route::resource('sureveystepthree','App\Http\Controllers\SurveyStepThreeController');


Route::put('/surveycomplete','App\Http\Controllers\SurveyCompleteController@update');
Route::put('/oncoc4surveycomplete','App\Http\Controllers\OncoC4SurveyCompleteController@update');

Route::put('/patientprofile','App\Http\Controllers\PatientProfileController@update');
Route::put('/patientprofilediagnosis','App\Http\Controllers\PatientProfileDiagnosisController@update');
Route::put('/patientprofiledoctors','App\Http\Controllers\PatientProfileDoctersController@update');

// Slim routes for performance
Route::put('/trialdetail','App\Http\Controllers\TrialDetailController@index');
Route::get('/triallist','App\Http\Controllers\TrialListController@index');
Route::put('/specialistdetail','App\Http\Controllers\SpecialistDetailController@index');
Route::get('/specialistlist','App\Http\Controllers\SpecialistListController@index');

//map routes:
Route::get('/specialistresult','App\Http\Controllers\SpecialistController@index');
Route::get('/trialresult','App\Http\Controllers\TrialController@index');


//new routes in work
//Route::get('/newtrialresult','App\Http\Controllers\NewTrialController@index');
//Route::get('/newspecialistresult','App\Http\Controllers\NewSpecialistController@index');

//new routes with pagination
//Route::get('/newtrialresultpagination','App\Http\Controllers\NewTrialPaginationController@index');
//Route::get('/newspecialistresultpagination','App\Http\Controllers\NewSpecialistPaginationController@index');

//payload routes in work
//Route::put('/payloadtrialresult','App\Http\Controllers\PayloadTrialController@index');
//Route::put('/payloadspecialistresult','App\Http\Controllers\PayloadSpecialistController@index');


//new route for education tab
Route::get('/education','App\Http\Controllers\EducationController@index');

//new route for favorite icon
Route::put('/userfavorites ','App\Http\Controllers\PatientFavoriteController@index');

//new routes for navigation guide
Route::put('/navigationguide','App\Http\Controllers\PatientNavigationguideController@update');
Route::get('/navigationguide','App\Http\Controllers\PatientNavigationguideController@index');

Route::resource('patientfull','App\Http\Controllers\PatientFullController');
Route::resource('patientdiagnosis','App\Http\Controllers\PatientDiagnosisController');
Route::resource('patientdoctor','App\Http\Controllers\PatientDoctorController');


Route::put('usertrace','App\Http\Controllers\UserTraceController@store');
Route::get('usercognito','App\Http\Controllers\UserCognitoController@index');

// routes to lookup table, which have no creates or updates (for select form fields)
Route::resource('lkuppatientethnicity','App\Http\Controllers\DropDowns\LkupPatientEthnicityController');
Route::resource('lkuppatientdiagnosistreatment','App\Http\Controllers\DropDowns\LkupPatientDiagnosisTreatmentController');
Route::resource('lkuppatientdiagnosisadditional','App\Http\Controllers\DropDowns\LkupPatientDiagnosisAdditionalController');
Route::resource('lkuppatientdiagnosisremotesite','App\Http\Controllers\DropDowns\LkupPatientDiagnosisRemoteSiteController');
Route::resource('lkuppatientdiagnosiscancerstage','App\Http\Controllers\DropDowns\LkupPatientDiagnosisCancerStageController');
Route::resource('lkuppatientdiagnosiscancertype','App\Http\Controllers\DropDowns\LkupPatientDiagnosisCancerTypeController');
Route::resource('lkuppatientdiagnosiscelltype','App\Http\Controllers\DropDowns\LkupPatientDiagnosisCellTypeController');
Route::resource('lkuppatientdiagnosisscore','App\Http\Controllers\DropDowns\LkupPatientDiagnosisPerformanceScoreController');
Route::resource('lkuppatientdiagnosistumorsite','App\Http\Controllers\DropDowns\LkupPatientDiagnosisTumorSiteController');
Route::resource('lkuppatientdiagnosistumorsize','App\Http\Controllers\DropDowns\LkupPatientDiagnosisTumorSizeController');
Route::resource('lkuppatientdiagnosisbiomarker','App\Http\Controllers\DropDowns\LkupPatientDiagnosisBiomarkerController');
Route::resource('lkuppatientdiagnosissubtype','App\Http\Controllers\DropDowns\LkupPatientDiagnosisSubTypeController');
Route::resource('lkuppatienteducationlevel','App\Http\Controllers\DropDowns\LkupPatientEducationLevelsController');

// search routes for auto complete fields
Route::get('/lkuppatientdiagnosisadditional/search/{label}',['App\Http\Controllers\DropDowns\LkupPatientDiagnosisAdditionalController', 'search']);
Route::get('/lkuppatientdiagnosistreatment/search/{label}',['App\Http\Controllers\DropDowns\LkupPatientDiagnosisTreatmentController', 'search']);
Route::get('/lkuppatientdiagnosisremotesite/search/{label}',['App\Http\Controllers\DropDowns\LkupPatientDiagnosisRemoteSiteController', 'search']);
Route::get('/lkuppatientdiagnosissubtype/search/{label}',['App\Http\Controllers\DropDowns\LkupPatientDiagnosisSubTypeController', 'search']);


/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/
