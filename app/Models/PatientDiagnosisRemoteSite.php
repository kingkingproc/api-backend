<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosisRemoteSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnosis_id',
        'remote_site_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function remote_sites() {

        return $this->hasMany('App\Models\LkupPatientDiagnosisRemoteSite', 'remote_site_id', 'remote_site_id');
    }
}
