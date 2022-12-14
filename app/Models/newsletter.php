<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class newsletter extends Model
{
    
    use HasFactory;
    protected $table = "newsletter";
    protected $primaryKey = 'newsletter_id';

    protected $fillable = [
        'name_first',
        'name_last',
        'email',
        'bln_marketing',
        'message',
        'role',
        'site',
        'source'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    
}
