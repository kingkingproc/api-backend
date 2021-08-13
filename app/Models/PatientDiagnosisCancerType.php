<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisCancerType extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancer_type_label'
    ];
}
