<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupContactType extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_type_id',
        'contact_type'
    ];
}
