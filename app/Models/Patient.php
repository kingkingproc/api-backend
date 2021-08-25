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
        'type',
        'sub',
        'name_first',
        'name_middle',
        'name_last',
        'dob',
        'sex',
        'ethnicity_id',
        'address_id',
        'is_complete'
    ];

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
