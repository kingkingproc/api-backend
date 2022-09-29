<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientNavigationguide extends Model
{
    use HasFactory;
    protected $table = "patient_navigationguide";
    protected $primaryKey = 'patient_navigation_id';

    protected $fillable = [
        'patient_id',
        'oncologist',
        'care_team',
        'biomarker_drugs',
        'biomarker_test_results',
        'treatment_options',
        'clinical_trials',
        'cancer_stage',
        'cancer_spread',
        'cancer_subtype',
        'systemic_treatment',
        'received_treatment',
        'ecog_performance_status',
        'hereditary_notes',
        'symptoms',
        'check_pathology_report',
        'check_post_operation',
        'check_blood_tests',
        'check_tissue_tests',
        'check_imaging',
        'check_medications',
        'check_symptoms',
        'check_medical_records',
        'data_collection_notes',
        'nccn_notes',
        'nci_notes',
        'asco_notes',
        'pubmed_notes',
        'acs_notes',
        'org_notes',
        'app_notes',
        'case_manager',
        'insurance_experts',
        'advocacy_organizations_1',
        'advocacy_organizations_2',
        'cost_coverage'
    ];
    
    protected $hidden = ['created_at', 'updated_at'];

}
