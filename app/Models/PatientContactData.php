<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientContactData extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'contact_data_type_id',
        'contact_data'
    ];
}
