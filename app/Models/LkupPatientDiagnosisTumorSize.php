<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisTumorSize extends Model
{
    use HasFactory;

    protected $primaryKey = 'tumor_size_id';

    protected $fillable = [
        'tumor_size_label'
    ];
}
