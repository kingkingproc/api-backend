<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisAdditional extends Model
{
    use HasFactory;
    protected $fillable = [
        'diagnosis_id',
        'additional_id'
    ];
}
