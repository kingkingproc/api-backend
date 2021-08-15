<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisPerformanceScore extends Model
{
    use HasFactory;
    protected $fillable = [
        'performance_score_label'
    ];
}
