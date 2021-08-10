<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'type',
        'password',
        'name_first',
        'name_middle',
        'name_last',
        'dob',
        'sex',
        'ethnicity',
        'primary_contact_id',
        'secondary_contact_id',
        'address_id'
    ];
}
