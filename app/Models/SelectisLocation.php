<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectisLocation extends Model
{
    use HasFactory;

    protected $connection = "pgsql2";

    protected $table = "location";

    protected $primaryKey = 'location_id';
}
