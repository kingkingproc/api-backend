<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectisTrial extends Model
{
    use HasFactory;

    protected $connection = "pgsql2";

    protected $table = "trial";

    protected $primaryKey = 'trial_id';


}
