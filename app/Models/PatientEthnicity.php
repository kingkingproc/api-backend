<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientEthnicity extends Model
{
    use HasFactory;

    protected $fillable = [
        'ethnicity_label'
    ];
}
