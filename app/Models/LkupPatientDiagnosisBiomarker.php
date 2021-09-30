<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisBiomarker extends Model
{
    use HasFactory;

    protected $primaryKey = 'biomarker_id';

    protected $fillable = [
        'biomarker_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
