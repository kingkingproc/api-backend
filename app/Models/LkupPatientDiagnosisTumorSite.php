<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupPatientDiagnosisTumorSite extends Model
{
    use HasFactory;

    protected $primaryKey = 'tumor_site_id';

    protected $fillable = [
        'tumor_site_label'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
