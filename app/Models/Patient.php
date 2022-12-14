<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Model
{
    use HasApiTokens, HasFactory;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'email',
        'user_type',
        'sub',
        'name_first',
        'name_middle',
        'name_last',
        'dob_day',
        'dob_month',
        'dob_year',
        'sex',
        'ethnicity_id',
        'address_id',
        'is_complete',
        'termsAgreement',
        'shareInformation',
        'sendInformation',
        'education_level',
        'is_medicaid_patient',
        'view_at'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    function getPatientContacts() {

        return $this->hasMany('App\Models\PatientContact', 'patient_id', 'patient_id');
    }

    function getAddresses() {

        return $this->belongsTo('App\Models\address', 'address_id', 'address_id');
    }

    function getDiagnosis() {

        return $this->hasOne('App\Models\PatientDiagnosis', 'patient_id', 'patient_id');
    }
   
}
