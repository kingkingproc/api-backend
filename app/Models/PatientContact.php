<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientContact extends Model
{
    use HasFactory;

    protected $primaryKey = 'contact_id';

    protected $fillable = [
        'contact_name',
        'patient_id',
        'address_id',
        'contact_type_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    function getPatientContactType() {

        return $this->hasOne('App\Models\LkupContactType', 'contact_type_id','contact_type_id');
    }

    function getPatientContactData() {

        return $this->hasOne('App\Models\PatientContactData', 'contact_id', 'contact_id');
    }

}
