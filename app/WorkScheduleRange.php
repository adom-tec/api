<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkScheduleRange extends Model
{
    protected $table = 'cfg.WorkScheduleRanges';
    protected $primaryKey = 'Id';

    protected $fillable = ['WorkScheduleId', 'Start', 'End'];

    public function workSchedule()
    {
        return $this->belongsTo('App\WorkSchedule', 'WorkScheduleId');
    }
}
