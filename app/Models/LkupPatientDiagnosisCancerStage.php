<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisCancerStage extends Model
{
    use HasFactory;

    protected $primaryKey = 'cancer_stage_id';

    protected $fillable = [
        'cancer_stage_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
