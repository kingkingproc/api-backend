<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisTumorSite extends Model
{
    use HasFactory;
    protected $fillable = [
        'tumor_site_label'
    ];
}
