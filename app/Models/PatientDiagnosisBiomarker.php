<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisBiomarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnosis_id',
        'biomarker_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
