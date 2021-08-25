<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LkupContactDataType extends Model
{
    use HasFactory;

    protected $primaryKey = 'contact_data_type_id';

    protected $fillable = [
        'contact_data_type'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
