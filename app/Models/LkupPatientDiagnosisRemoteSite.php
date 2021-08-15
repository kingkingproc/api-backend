<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisRemoteSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'remote_site_label'
    ];
}
