<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyStepOne extends Model
{
    use HasFactory;

    protected $table = 'patients';

    protected $fillable = ["name_first", "name_middle", "name_last"];
}