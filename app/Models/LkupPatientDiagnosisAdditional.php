<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisAdditional extends Model
{
    use HasFactory;

    protected $primaryKey = 'additional_id';

    protected $fillable = [
        'additional_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
    
}
