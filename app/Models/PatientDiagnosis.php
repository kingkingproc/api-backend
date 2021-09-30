<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDiagnosis extends Model
{
    use HasFactory;
    protected $primaryKey = 'diagnosis_id';

    protected $fillable = [
        'patient_id',
        'cancer_type_id',
        'cell_type_id',
        'stage_id',
        'tumor_size_id',
        'tumor_site_id',
        'performance_score_id',
        'pathology',
        'dod_month',
        'dod_day',
        'dod_year',
        'is_brain_tumor',
        'is_metastatic',
        'cancer_sub_type_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function cancer_type() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisCancerType', 'cancer_type_id', 'cancer_type_id');
    }

    public function cancer_sub_type() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisCancerSubType', 'cancer_sub_type_id', 'cancer_sub_type_id');
    }

    public function cell_type() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisCellType', 'cell_type_id', 'cell_type_id');
    }

    public function stage() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisCancerStage', 'cancer_stage_id', 'stage_id');
    }

    public function tumor_size() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisTumorSize', 'tumor_size_id', 'tumor_size_id');
    }

    public function tumor_site() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisTumorSite', 'tumor_site_id', 'tumor_site_id');
    }

    public function performance_score() {

        return $this->hasOne('App\Models\LkupPatientDiagnosisPerformanceScore', 'performance_score_id', 'performance_score_id');
    }

    public function treatment() {
        return $this->hasMany('App\Models\PatientDiagnosisTreatment', 'diagnosis_id','diagnosis_id');
    }

    public function remote_site() {
        return $this->hasMany('App\Models\PatientDiagnosisRemoteSite', 'diagnosis_id','diagnosis_id');
    }

    public function additional() {
        return $this->hasMany('App\Models\PatientDiagnosisAdditional', 'diagnosis_id','diagnosis_id');
    }

    public function biomarker() {
        return $this->hasMany('App\Models\PatientDiagnosisBiomarker', 'diagnosis_id','diagnosis_id');
    }
}
