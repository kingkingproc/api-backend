<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupContactType extends Model
{
    use HasFactory;

    protected $primaryKey = 'contact_type_id';

    protected $fillable = [
        'contact_type'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
