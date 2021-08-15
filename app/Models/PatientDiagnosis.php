<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosis extends Model
{
    use HasFactory;
    protected $primaryKey = 'diagnosis_id';

    protected $fillable = [
        'patient_id',
        'cancer_type_id',
        'cell_type_id',
        'stage_id',
        'tumor_size_id',
        'tumor_site_id',
        'performance_score_id',
        'pathology',
        'dod'
    ];
}
