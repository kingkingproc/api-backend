<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisCancerStage extends Model
{
    use HasFactory;
    protected $fillable = [
        'cancer_stage_label'
    ];
}
