<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisCellType extends Model
{
    use HasFactory;

    protected $primaryKey = 'cell_type_id';

    protected $fillable = [
        'cell_type_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
