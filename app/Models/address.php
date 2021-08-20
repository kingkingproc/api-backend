<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class address extends Model
{
    
    use HasFactory;

    protected $primaryKey = 'address_id';

    protected $fillable = [
        'address_1',
        'address_2',
        'address_city',
        'address_state',
        'address_zip',
        'address_country'
    ];



    
}
