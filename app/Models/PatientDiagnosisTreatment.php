<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_id',
        'treatment_label'
    ];
}