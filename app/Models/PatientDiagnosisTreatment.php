<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnosis_id',
        'treatment_id'
    ];

    public function treatments() {

        return $this->hasMany('App\Models\LkupPatientDiagnosisTreatment', 'treatment_id', 'treatment_id');
    }
}
