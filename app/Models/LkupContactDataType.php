<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupContactDataType extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_data_type'
    ];
}
