<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class address extends Model
{
    

    protected $fillable = [
        'address_1',
        'address_2',
        'address_city',
        'address_state',
        'address_zip',
        'address_country'
    ];

    use HasFactory;
}
