<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientEthnicity extends Model
{
    use HasFactory;

    protected $primaryKey = 'ethnicity_id';

    protected $fillable = [
        'ethnicity_label'
    ];
}
