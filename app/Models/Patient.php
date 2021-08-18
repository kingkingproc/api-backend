<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

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

        return $this->hasMany('App\Models\PatientContact');
    }

    function getAddresses() {

        return $this->belongsTo('App\Models\address', 'address_id', 'id');
    }

    function getDiagnosis() {

        return $this->hasOne('App\Models\PatientDiagnosis', 'patient_id', 'id');
    }

    /*
    function getPatientContactData() {
        $contacts = $this->hasMany('App\Models\PatientContact');
        //$contacts = json_decode($contacts);
        //foreach($contacts as $contact) {
        //    $array[] = $contact->hasMany('App\Models\PatientContactData');
        //}
        return $contacts->hasMany('App\Models\PatientContactData');;
    }
        */
    
}
