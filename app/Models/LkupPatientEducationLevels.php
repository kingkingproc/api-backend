<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientEducationLevels extends Model
{
    use HasFactory;

    protected $primaryKey = 'education_level_id';

    protected $fillable = [
        'education_level_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
