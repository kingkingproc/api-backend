<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisTreatment extends Model
{
    use HasFactory;

    protected $primaryKey = 'treatment_id';

    protected $fillable = [
        'treatment_label'
    ];
}
