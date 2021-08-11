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
        'ethnicity',
        'address_id'
    ];

    function getPatientContacts() {

        return $this->hasMany('App\Models\PatientContact');
    }
}
