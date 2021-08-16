<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisAdditional extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnosis_id',
        'additional_id'
    ];

    public function additionals() {

        return $this->hasMany('App\Models\LkupPatientDiagnosisAdditional', 'additional_id', 'additional_id');
    }
}
