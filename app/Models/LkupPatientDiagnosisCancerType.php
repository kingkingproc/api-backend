<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisCancerType extends Model
{
    use HasFactory;

    protected $primaryKey = 'cancer_type_id';

    protected $fillable = [
        'cancer_type_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
