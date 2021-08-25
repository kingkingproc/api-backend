<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientContactData extends Model
{
    use HasFactory;

    protected $primaryKey = 'patient_contact_data_id';

    protected $fillable = [
        'contact_id',
        'contact_data_type_id',
        'contact_data'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    function getPatientContactDataType() {

        return $this->hasOne('App\Models\LkupContactDataType', 'contact_data_type_id', 'contact_data_type_id');
    }

    function getFromContact() {
        return $this->belongsTo('App\Models\PatientContact', 'contact_id', 'contact_id');
    }
}
