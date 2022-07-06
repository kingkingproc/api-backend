<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientFavorite extends Model
{
    use HasFactory;

    protected $primaryKey = 'favorite_id';

    protected $fillable = [
        'patient_id',
        'sub',
        'type',
        'type_id',
        'location_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];

}