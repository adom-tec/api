<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $table = 'cfg.WorkSchedules';
    protected $fillable = ['Name'];
}
