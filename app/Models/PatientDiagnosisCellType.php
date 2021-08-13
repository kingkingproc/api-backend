<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisCellType extends Model
{
    use HasFactory;

    protected $fillable = [
        'cell_type_label'
    ];
}
