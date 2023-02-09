<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisCancerSubType extends Model
{
    use HasFactory;

    protected $primaryKey = 'cancer_sub_type_id';

    protected $fillable = [
        'cancer_sub_type_label',
        'cancer_type_id',
        'cancer_sub_type_synonyms',
        'cancer_sub_type_antonyms'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
