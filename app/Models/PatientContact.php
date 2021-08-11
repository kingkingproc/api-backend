<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientContact extends Model
{
    use HasFactory;

    protected $fillable[
        'contact_name',
        'contact_data',
        'contact_type_id'
    ]
}
